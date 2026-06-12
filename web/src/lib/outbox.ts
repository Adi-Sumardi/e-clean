/**
 * Antrian kirim offline (outbox pattern).
 *
 * Submit laporan SELALU masuk antrian lokal dulu → UI langsung memberi umpan
 * balik "tersimpan", lalu sync.ts mengirim ke server saat online. Lihat desain §4.
 */

import { getDB, OUTBOX_STORE, type OutboxJob } from "./db";

type Listener = (count: number) => void;
const listeners = new Set<Listener>();

const newId = () => `${Date.now()}-${Math.random().toString(36).slice(2, 8)}`;

/** Idempotency-Key sederhana & unik per job. */
function newIdempotencyKey(): string {
  if (typeof crypto !== "undefined" && "randomUUID" in crypto) {
    return crypto.randomUUID();
  }
  return `${Date.now()}-${Math.random().toString(36).slice(2)}`;
}

async function notify(): Promise<void> {
  const count = await pendingCount();
  listeners.forEach((l) => l(count));
}

/** Berlangganan perubahan jumlah pending. Mengembalikan fungsi unsubscribe. */
export function subscribeOutbox(listener: Listener): () => void {
  listeners.add(listener);
  void pendingCount().then(listener);
  return () => listeners.delete(listener);
}

export async function pendingCount(): Promise<number> {
  const db = await getDB();
  return db.count(OUTBOX_STORE);
}

export async function allJobs(): Promise<OutboxJob[]> {
  const db = await getDB();
  const jobs = await db.getAll(OUTBOX_STORE);
  return jobs.sort((a, b) => a.createdAt - b.createdAt);
}

export async function enqueue(input: {
  domain: OutboxJob["domain"];
  endpoint: string;
  fields: Record<string, string>;
  photos: Record<string, Blob[]>;
  label: string;
}): Promise<OutboxJob> {
  const job: OutboxJob = {
    id: newId(),
    idempotencyKey: newIdempotencyKey(),
    domain: input.domain,
    endpoint: input.endpoint,
    fields: input.fields,
    photos: input.photos,
    label: input.label,
    createdAt: Date.now(),
    attempts: 0,
    status: "pending",
  };
  const db = await getDB();
  await db.put(OUTBOX_STORE, job);
  await notify();
  return job;
}

export async function updateJob(job: OutboxJob): Promise<void> {
  const db = await getDB();
  await db.put(OUTBOX_STORE, job);
  await notify();
}

export async function removeJob(id: string): Promise<void> {
  const db = await getDB();
  await db.delete(OUTBOX_STORE, id);
  await notify();
}
