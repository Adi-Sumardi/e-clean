/**
 * Web Push (VAPID) di sisi PWA.
 *
 * Alur aktifkan: minta izin → subscribe via PushManager dengan VAPID public key
 * dari server → kirim subscription ke backend. Service worker menangani event
 * "push" & "notificationclick" (lihat public/sw.js).
 */

import { api } from "./api";

export function pushSupported(): boolean {
  return (
    typeof window !== "undefined" &&
    "serviceWorker" in navigator &&
    "PushManager" in window &&
    "Notification" in window
  );
}

export function permission(): NotificationPermission {
  return typeof Notification !== "undefined" ? Notification.permission : "denied";
}

function urlBase64ToUint8Array(base64: string): Uint8Array {
  const padding = "=".repeat((4 - (base64.length % 4)) % 4);
  const b64 = (base64 + padding).replace(/-/g, "+").replace(/_/g, "/");
  const raw = atob(b64);
  const arr = new Uint8Array(raw.length);
  for (let i = 0; i < raw.length; i++) arr[i] = raw.charCodeAt(i);
  return arr;
}

/** Apakah perangkat ini sudah punya langganan push aktif? */
export async function isSubscribed(): Promise<boolean> {
  if (!pushSupported()) return false;
  const reg = await navigator.serviceWorker.ready;
  return !!(await reg.pushManager.getSubscription());
}

/** Aktifkan notifikasi: izin → subscribe → daftarkan ke server. */
export async function enablePush(): Promise<void> {
  if (!pushSupported()) throw new Error("Perangkat tidak mendukung notifikasi.");

  const perm = await Notification.requestPermission();
  if (perm !== "granted") throw new Error("Izin notifikasi ditolak.");

  const { public_key } = await api.get<{ public_key: string }>(
    "/auth/vapid-public-key",
  );
  if (!public_key) throw new Error("Server belum dikonfigurasi untuk push.");

  const reg = await navigator.serviceWorker.ready;
  const sub =
    (await reg.pushManager.getSubscription()) ??
    (await reg.pushManager.subscribe({
      userVisibleOnly: true,
      applicationServerKey: urlBase64ToUint8Array(public_key) as BufferSource,
    }));

  const json = sub.toJSON() as {
    endpoint?: string;
    keys?: { p256dh?: string; auth?: string };
  };

  await api.post("/auth/web-push-subscription", {
    json: {
      endpoint: json.endpoint,
      keys: { p256dh: json.keys?.p256dh, auth: json.keys?.auth },
    },
  });
}

/** Matikan notifikasi: hapus langganan lokal + di server. */
export async function disablePush(): Promise<void> {
  if (!pushSupported()) return;
  const reg = await navigator.serviceWorker.ready;
  const sub = await reg.pushManager.getSubscription();
  if (!sub) return;

  const endpoint = sub.endpoint;
  await sub.unsubscribe();
  try {
    await api.delete("/auth/web-push-subscription", { json: { endpoint } });
  } catch {
    /* abaikan — langganan lokal sudah dilepas */
  }
}
