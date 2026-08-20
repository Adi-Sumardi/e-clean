import AsyncStorage from "@react-native-async-storage/async-storage";
import { activityReportService, fieldService, type FieldScope } from "./services";
import { ApiError } from "./api";

/**
 * Offline-first queue for report submissions.
 *
 * When a report submission fails because the device is offline (a network/
 * transport error rather than a server validation error), it is persisted to
 * AsyncStorage with an Idempotency-Key and retried later — on app foreground,
 * reconnection, or manual sync.
 */

const QUEUE_KEY = "eclean.offline.queue.v1";

export type QueuedJob = {
  id: string;
  idempotencyKey: string;
  status: "pending" | "failed";
  attempts: number;
  lastError?: string;
  createdAt: number;
} & (
  | {
      kind: "activity-report";
      payload: Parameters<typeof activityReportService.create>[0];
    }
  | {
      kind: "field-report";
      scope: FieldScope;
      fields: Record<string, unknown>;
      photos: Record<string, string[]>;
    }
);

type Listener = (count: number) => void;
const listeners = new Set<Listener>();

function notify(count: number) {
  listeners.forEach((l) => l(count));
}

/** Subscribe to pending-count changes. Returns an unsubscribe function. */
export function subscribeQueue(listener: Listener): () => void {
  listeners.add(listener);
  void pendingCount().then(listener);
  return () => listeners.delete(listener);
}

async function getQueue(): Promise<QueuedJob[]> {
  try {
    const raw = await AsyncStorage.getItem(QUEUE_KEY);
    return raw ? (JSON.parse(raw) as QueuedJob[]) : [];
  } catch {
    return [];
  }
}

async function setQueue(jobs: QueuedJob[]): Promise<void> {
  await AsyncStorage.setItem(QUEUE_KEY, JSON.stringify(jobs));
  const pending = jobs.filter((j) => j.status !== "failed").length;
  notify(pending);
}

export async function pendingCount(): Promise<number> {
  const q = await getQueue();
  return q.filter((j) => j.status !== "failed").length;
}

export async function failedCount(): Promise<number> {
  const q = await getQueue();
  return q.filter((j) => j.status === "failed").length;
}

export async function clearFailedJobs(): Promise<void> {
  const q = await getQueue();
  const remaining = q.filter((j) => j.status !== "failed");
  await setQueue(remaining);
}

export async function retryFailedJobs(): Promise<number> {
  const q = await getQueue();
  const reset = q.map((j) => (j.status === "failed" ? { ...j, status: "pending" as const } : j));
  await setQueue(reset);
  return syncQueue();
}

async function enqueue(job: QueuedJob): Promise<void> {
  const q = await getQueue();
  q.push(job);
  await setQueue(q);
}

/** True when an error means "no connection" (retryable) vs a server rejection. */
function isOfflineError(err: unknown): boolean {
  return err instanceof ApiError && err.status === undefined;
}

const newId = () => `${Date.now()}-${Math.random().toString(36).slice(2, 8)}`;

function newIdempotencyKey(): string {
  if (typeof crypto !== "undefined" && "randomUUID" in crypto) {
    return crypto.randomUUID();
  }
  return `${Date.now()}-${Math.random().toString(36).slice(2, 10)}`;
}

/**
 * Submit an activity report, queueing it for later if the device is offline.
 * Returns 'sent' when it reached the server, or 'queued' when stored locally.
 * Re-throws server-side errors (e.g. validation) so the UI can show them.
 */
export async function submitActivityReport(
  payload: Parameters<typeof activityReportService.create>[0]
): Promise<"sent" | "queued"> {
  const idempotencyKey = newIdempotencyKey();
  try {
    await activityReportService.create(payload, idempotencyKey);
    return "sent";
  } catch (err) {
    if (isOfflineError(err)) {
      await enqueue({
        id: newId(),
        idempotencyKey,
        status: "pending",
        attempts: 0,
        kind: "activity-report",
        createdAt: Date.now(),
        payload,
      });
      return "queued";
    }
    throw err;
  }
}

export async function submitFieldReport(
  scope: FieldScope,
  fields: Record<string, unknown>,
  photos: Record<string, string[]> = {}
): Promise<"sent" | "queued"> {
  const idempotencyKey = newIdempotencyKey();
  try {
    await fieldService.createLaporan(scope, fields, photos, idempotencyKey);
    return "sent";
  } catch (err) {
    if (isOfflineError(err)) {
      await enqueue({
        id: newId(),
        idempotencyKey,
        status: "pending",
        attempts: 0,
        kind: "field-report",
        createdAt: Date.now(),
        scope,
        fields,
        photos,
      });
      return "queued";
    }
    throw err;
  }
}

let syncing = false;

/**
 * Flush queued jobs. Stops on the first job that fails for being offline;
 * preserves failed jobs with status: "failed" rather than silently deleting them.
 */
export async function syncQueue(): Promise<number> {
  if (syncing) return 0;
  syncing = true;
  let sent = 0;
  try {
    const q = await getQueue();
    const remaining: QueuedJob[] = [];

    for (const job of q) {
      if (job.status === "failed") {
        remaining.push(job);
        continue;
      }

      const attempted: QueuedJob = { ...job, attempts: (job.attempts ?? 0) + 1 };
      try {
        if (job.kind === "activity-report") {
          await activityReportService.create(job.payload, job.idempotencyKey);
        } else {
          await fieldService.createLaporan(job.scope, job.fields, job.photos, job.idempotencyKey);
        }
        sent++;
      } catch (err) {
        if (isOfflineError(err)) {
          // Masih offline — pertahankan job ini dan seluruh job setelahnya
          remaining.push(attempted);
          const idx = q.indexOf(job);
          remaining.push(...q.slice(idx + 1));
          break;
        }

        if (err instanceof ApiError && err.status === 401) {
          // Token sesi kedaluwarsa — simpan sebagai pending agar sync otomatis saat login ulang
          remaining.push({ ...attempted, status: "pending", lastError: "Sesi login kedaluwarsa." });
          break;
        }

        // Error server (422 validasi / 500) — tandai failed, JANGAN hapus diam-diam agar data tidak hilang
        remaining.push({
          ...attempted,
          status: "failed",
          lastError: err instanceof ApiError ? err.message : "Gagal mengirim laporan.",
        });
      }
    }

    await setQueue(remaining);
    return sent;
  } finally {
    syncing = false;
  }
}
