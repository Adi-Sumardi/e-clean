// @vitest-environment node
/**
 * Test mesin sync outbox — kontrak inti offline-first:
 *  - 2xx → job dihapus
 *  - error transport (offline) → job tetap pending, loop berhenti
 *  - 4xx validasi → job ditandai failed (tidak retry membabi buta)
 *  - header Idempotency-Key selalu terkirim (anti laporan dobel)
 *
 * Environment node agar Blob foto selamat melewati fake-indexeddb.
 */
import "fake-indexeddb/auto";
import { describe, it, expect, beforeEach, afterEach, vi } from "vitest";
import { enqueue, allJobs, removeJob, updateJob } from "@/lib/outbox";
import { syncOutbox, isOnline, onSynced } from "@/lib/sync";

const fetchMock = vi.fn();

beforeEach(async () => {
  vi.stubGlobal("fetch", fetchMock);
  // navigator Node 22 tidak punya onLine → stub agar dianggap online.
  vi.stubGlobal("navigator", { onLine: true });
  fetchMock.mockReset();
  for (const job of await allJobs()) await removeJob(job.id);
});

afterEach(() => {
  vi.unstubAllGlobals();
});

function ok(body: unknown = { success: true }) {
  return new Response(JSON.stringify(body), { status: 201 });
}

function contoh(label: string, endpoint = "/activity-reports") {
  return {
    domain: "kebersihan" as const,
    endpoint,
    fields: { kegiatan: "Tes", jam_mulai: "08:00" },
    photos: { foto_sebelum: [new Blob(["x"], { type: "image/jpeg" })] },
    label,
  };
}

/**
 * Enqueue berurutan dengan createdAt eksplisit — dua enqueue() bisa jatuh di
 * milidetik yang sama sehingga urutan FIFO allJobs() jadi ambigu di test.
 */
async function enqueueBerurutan(...labels: string[]) {
  const base = Date.now();
  const jobs = [];
  for (let i = 0; i < labels.length; i++) {
    const job = await enqueue(contoh(labels[i]));
    const updated = { ...job, createdAt: base + i * 1000 };
    await updateJob(updated);
    jobs.push(updated);
  }
  return jobs;
}

describe("syncOutbox sukses", () => {
  it("mengirim semua job lalu menghapusnya; listener onSynced terpanggil", async () => {
    await enqueue(contoh("a"));
    await enqueue(contoh("b", "/satpam/laporan"));
    fetchMock.mockResolvedValue(ok());

    let synced = false;
    const unsub = onSynced(() => (synced = true));

    const sent = await syncOutbox();

    expect(sent).toBe(2);
    expect(await allJobs()).toHaveLength(0);
    expect(synced).toBe(true);
    unsub();
  });

  it("mengirim header Idempotency-Key + FormData foto bergaya PHP array", async () => {
    const job = await enqueue(contoh("x"));
    fetchMock.mockResolvedValue(ok());

    await syncOutbox();

    const [url, init] = fetchMock.mock.calls[0];
    expect(url).toBe("/api/v1/activity-reports");
    expect(init.method).toBe("POST");
    expect((init.headers as Record<string, string>)["Idempotency-Key"]).toBe(
      job.idempotencyKey,
    );

    const fd = init.body as FormData;
    expect(fd.get("kegiatan")).toBe("Tes");
    const foto = fd.get("foto_sebelum[]");
    expect(foto).toBeInstanceOf(File);
    expect((foto as File).name).toBe("foto_sebelum-0.jpg");
  });
});

describe("syncOutbox saat offline", () => {
  it("error transport → job kembali pending, loop berhenti (job lain tak disentuh)", async () => {
    await enqueueBerurutan("pertama", "kedua");
    fetchMock.mockRejectedValue(new TypeError("Failed to fetch"));

    const sent = await syncOutbox();

    expect(sent).toBe(0);
    const jobs = await allJobs();
    expect(jobs).toHaveLength(2);
    expect(jobs[0].status).toBe("pending"); // dikembalikan, BUKAN failed
    expect(jobs[0].attempts).toBe(1); // tercatat sudah dicoba
    expect(jobs[1].attempts).toBe(0); // loop berhenti sebelum job kedua
    expect(fetchMock).toHaveBeenCalledTimes(1);
  });

  it("navigator.onLine=false → tidak mencoba mengirim sama sekali", async () => {
    await enqueue(contoh("x"));
    vi.stubGlobal("navigator", { onLine: false });

    expect(isOnline()).toBe(false);
    const sent = await syncOutbox();

    expect(sent).toBe(0);
    expect(fetchMock).not.toHaveBeenCalled();
    expect(await allJobs()).toHaveLength(1);
  });
});

describe("syncOutbox error validasi server", () => {
  it("4xx → job failed dengan pesan server, job lain tetap diproses", async () => {
    await enqueueBerurutan("gagal", "sukses");

    fetchMock
      .mockResolvedValueOnce(
        new Response(
          JSON.stringify({ success: false, message: "Kegiatan wajib diisi" }),
          { status: 422 },
        ),
      )
      .mockResolvedValueOnce(ok());

    const sent = await syncOutbox();

    expect(sent).toBe(1); // hanya yang sukses
    const jobs = await allJobs();
    expect(jobs).toHaveLength(1);
    expect(jobs[0].label).toBe("gagal");
    expect(jobs[0].status).toBe("failed");
    expect(jobs[0].lastError).toBe("Kegiatan wajib diisi");
  });

  it("job failed TIDAK ikut di-retry membabi buta… kecuali di-sync ulang manual", async () => {
    await enqueue(contoh("x"));
    fetchMock.mockResolvedValue(
      new Response(JSON.stringify({ message: "Invalid" }), { status: 422 }),
    );

    await syncOutbox();
    const [failed] = await allJobs();
    expect(failed.status).toBe("failed");

    // syncOutbox berikutnya tetap mencoba job failed (retry manual user) —
    // tapi yang berstatus "syncing" dilewati.
    fetchMock.mockResolvedValue(ok());
    const sent = await syncOutbox();
    expect(sent).toBe(1);
    expect(await allJobs()).toHaveLength(0);
  });
});
