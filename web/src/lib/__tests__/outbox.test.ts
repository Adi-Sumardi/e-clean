// @vitest-environment node
/**
 * Test outbox pattern (antrian laporan offline) di atas fake-indexeddb.
 * Inilah penjamin "laporan petugas tidak hilang walau tanpa sinyal".
 *
 * Environment node (bukan jsdom): structuredClone Node mendukung Blob,
 * sehingga round-trip foto Blob lewat fake-indexeddb teruji sungguhan.
 */
import "fake-indexeddb/auto";
import { describe, it, expect, beforeEach } from "vitest";
import {
  enqueue,
  allJobs,
  pendingCount,
  updateJob,
  removeJob,
  subscribeOutbox,
} from "@/lib/outbox";

async function clearOutbox() {
  for (const job of await allJobs()) {
    await removeJob(job.id);
  }
}

beforeEach(clearOutbox);

function contoh(label = "Toilet Lantai 1") {
  return {
    domain: "kebersihan" as const,
    endpoint: "/activity-reports",
    fields: { kegiatan: "Bersih toilet", jam_mulai: "08:00" },
    photos: { foto_sebelum: [new Blob(["x"], { type: "image/jpeg" })] },
    label,
  };
}

describe("enqueue", () => {
  it("menyimpan job pending dengan idempotency key unik", async () => {
    const a = await enqueue(contoh());
    const b = await enqueue(contoh("Lobi"));

    expect(a.status).toBe("pending");
    expect(a.attempts).toBe(0);
    expect(a.idempotencyKey).toBeTruthy();
    expect(a.idempotencyKey).not.toBe(b.idempotencyKey);
    expect(await pendingCount()).toBe(2);
  });

  it("menyimpan foto sebagai Blob (tahan reload, bukan object URL)", async () => {
    await enqueue(contoh());
    const [job] = await allJobs();
    expect(job.photos.foto_sebelum[0]).toBeInstanceOf(Blob);
    expect(job.fields.kegiatan).toBe("Bersih toilet");
  });
});

describe("allJobs", () => {
  it("terurut dari yang paling lama (FIFO untuk sync)", async () => {
    const a = await enqueue(contoh("pertama"));
    // createdAt pakai Date.now() — paksa beda waktu.
    const b = await enqueue(contoh("kedua"));
    await updateJob({ ...b, createdAt: a.createdAt + 1000 });

    const jobs = await allJobs();
    expect(jobs.map((j) => j.label)).toEqual(["pertama", "kedua"]);
  });
});

describe("updateJob / removeJob", () => {
  it("memperbarui status & menghapus job", async () => {
    const job = await enqueue(contoh());
    await updateJob({ ...job, status: "failed", lastError: "Validasi gagal" });

    const [stored] = await allJobs();
    expect(stored.status).toBe("failed");
    expect(stored.lastError).toBe("Validasi gagal");

    await removeJob(job.id);
    expect(await pendingCount()).toBe(0);
  });
});

describe("subscribeOutbox", () => {
  it("memberi tahu jumlah pending saat berubah; unsubscribe berhenti", async () => {
    const seen: number[] = [];
    const unsub = subscribeOutbox((n) => seen.push(n));
    await new Promise((r) => setTimeout(r, 10)); // pemberitahuan awal

    const job = await enqueue(contoh());
    await removeJob(job.id);

    expect(seen).toContain(0); // initial
    expect(seen).toContain(1); // setelah enqueue

    seen.length = 0;
    unsub();
    await enqueue(contoh());
    expect(seen).toHaveLength(0);
  });
});
