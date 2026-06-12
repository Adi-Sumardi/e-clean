/**
 * Mesin sinkronisasi outbox.
 *
 * Mengirim job tertunda ke server. Dipanggil saat: online kembali, app dibuka,
 * setelah enqueue, atau tombol "Sync sekarang". Membedakan:
 *  - sukses (2xx) → hapus job, segarkan cache laporan
 *  - error transport (offline) → biarkan pending, retry nanti
 *  - error validasi server (4xx) → tandai failed (jangan retry membabi buta)
 *
 * Idempotency-Key mencegah laporan dobel bila respons hilang setelah server
 * sebenarnya sudah menerima.
 */

import { API_BASE, ApiError, isOfflineError } from "./api";
import { getToken } from "./auth";
import { allJobs, updateJob, removeJob } from "./outbox";
import type { OutboxJob } from "./db";

let syncing = false;
const syncListeners = new Set<() => void>();

/** Berlangganan event "sync selesai" (untuk invalidasi query laporan). */
export function onSynced(cb: () => void): () => void {
  syncListeners.add(cb);
  return () => syncListeners.delete(cb);
}

export function isOnline(): boolean {
  return typeof navigator === "undefined" ? true : navigator.onLine;
}

function buildFormData(job: OutboxJob): FormData {
  const fd = new FormData();
  for (const [k, v] of Object.entries(job.fields)) fd.append(k, v);
  for (const [field, blobs] of Object.entries(job.photos)) {
    blobs.forEach((blob, i) => {
      // Nama field array PHP: foto_sebelum[] dsb.
      fd.append(`${field}[]`, blob, `${field}-${i}.jpg`);
    });
  }
  return fd;
}

async function sendJob(job: OutboxJob): Promise<void> {
  const token = getToken();
  const res = await fetch(`${API_BASE}${job.endpoint}`, {
    method: "POST",
    headers: {
      Accept: "application/json",
      "Idempotency-Key": job.idempotencyKey,
      ...(token ? { Authorization: `Bearer ${token}` } : {}),
    },
    body: buildFormData(job),
  });

  if (res.ok) return;

  let message = `Gagal (${res.status}).`;
  try {
    const body = await res.json();
    message = body?.message || message;
  } catch {
    /* abaikan */
  }
  throw new ApiError(message, res.status);
}

/** Proses semua job pending. Mengembalikan jumlah yang berhasil terkirim. */
export async function syncOutbox(): Promise<number> {
  if (syncing || !isOnline()) return 0;
  syncing = true;
  let sent = 0;
  try {
    const jobs = await allJobs();
    for (const job of jobs) {
      if (job.status === "syncing") continue;
      try {
        await updateJob({ ...job, status: "syncing", attempts: job.attempts + 1 });
        await sendJob(job);
        await removeJob(job.id);
        sent++;
      } catch (err) {
        if (isOfflineError(err)) {
          // Tidak ada koneksi → kembalikan ke pending, hentikan loop.
          await updateJob({ ...job, status: "pending" });
          break;
        }
        // Error server (validasi/otorisasi) → tandai failed agar tidak loop.
        await updateJob({
          ...job,
          status: "failed",
          lastError: err instanceof ApiError ? err.message : "Gagal mengirim.",
        });
      }
    }
  } finally {
    syncing = false;
    if (sent > 0) syncListeners.forEach((cb) => cb());
  }
  return sent;
}
