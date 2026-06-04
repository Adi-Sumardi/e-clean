import AsyncStorage from "@react-native-async-storage/async-storage";
import { activityReportService, fieldService, type FieldScope } from "./services";
import { ApiError } from "./api";

/**
 * Offline-first queue for report submissions.
 *
 * When a report submission fails because the device is offline (a network/
 * transport error rather than a server validation error), it is persisted to
 * AsyncStorage and retried later — on app foreground or manual sync. Photos are
 * local file URIs that survive across launches, so re-submission works.
 */

const QUEUE_KEY = "eclean.offline.queue.v1";

export type QueuedJob =
  | {
      id: string;
      kind: "activity-report";
      createdAt: number;
      payload: Parameters<typeof activityReportService.create>[0];
    }
  | {
      id: string;
      kind: "field-report";
      createdAt: number;
      scope: FieldScope;
      fields: Record<string, unknown>;
      photos: Record<string, string[]>;
    };

type Listener = (count: number) => void;
const listeners = new Set<Listener>();

function notify(count: number) {
  listeners.forEach((l) => l(count));
}

/** Subscribe to pending-count changes. Returns an unsubscribe function. */
export function subscribeQueue(listener: Listener): () => void {
  listeners.add(listener);
  void getQueue().then((q) => listener(q.length));
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
  notify(jobs.length);
}

export async function pendingCount(): Promise<number> {
  return (await getQueue()).length;
}

async function enqueue(job: QueuedJob): Promise<void> {
  const q = await getQueue();
  q.push(job);
  await setQueue(q);
}

/** True when an error means "no connection" (retryable) vs a server rejection. */
function isOfflineError(err: unknown): boolean {
  // ApiError without an HTTP status came from a transport/network failure.
  return err instanceof ApiError && err.status === undefined;
}

const newId = () => `${Date.now()}-${Math.random().toString(36).slice(2, 8)}`;

/**
 * Submit an activity report, queueing it for later if the device is offline.
 * Returns 'sent' when it reached the server, or 'queued' when stored locally.
 * Re-throws server-side errors (e.g. validation) so the UI can show them.
 */
export async function submitActivityReport(
  payload: Parameters<typeof activityReportService.create>[0]
): Promise<"sent" | "queued"> {
  try {
    await activityReportService.create(payload);
    return "sent";
  } catch (err) {
    if (isOfflineError(err)) {
      await enqueue({ id: newId(), kind: "activity-report", createdAt: Date.now(), payload });
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
  try {
    await fieldService.createLaporan(scope, fields, photos);
    return "sent";
  } catch (err) {
    if (isOfflineError(err)) {
      await enqueue({ id: newId(), kind: "field-report", createdAt: Date.now(), scope, fields, photos });
      return "queued";
    }
    throw err;
  }
}

let syncing = false;

/**
 * Flush queued jobs. Stops on the first job that fails for being offline (we
 * are still offline); drops jobs the server permanently rejects. Returns how
 * many were successfully sent.
 */
export async function syncQueue(): Promise<number> {
  if (syncing) return 0;
  syncing = true;
  let sent = 0;
  try {
    let q = await getQueue();
    const remaining: QueuedJob[] = [];

    for (const job of q) {
      try {
        if (job.kind === "activity-report") {
          await activityReportService.create(job.payload);
        } else {
          await fieldService.createLaporan(job.scope, job.fields, job.photos);
        }
        sent++;
      } catch (err) {
        if (isOfflineError(err)) {
          // Still offline — keep this and all later jobs for next time.
          remaining.push(job);
          const idx = q.indexOf(job);
          remaining.push(...q.slice(idx + 1));
          break;
        }
        // Server rejected it permanently — drop it so the queue can drain.
      }
    }

    await setQueue(remaining);
    return sent;
  } finally {
    syncing = false;
  }
}
