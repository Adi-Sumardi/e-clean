/* Service worker Apps KopkarYAPI PWA — Fase 0.
 *
 * Tujuan utama: refresh halaman TIDAK men-download ulang asset.
 *  - Asset Next.js (/_next/static/*) sudah content-hashed → CacheFirst (immutable).
 *  - Dokumen HTML & navigasi → StaleWhileRevalidate (tampil instan dari cache,
 *    diperbarui di latar belakang).
 *  - Path milik Laravel (API, Filament, storage) DILEWATI total — SW tidak boleh
 *    meng-cache/ikut campur, karena origin ini dibagi dengan backend.
 *
 * Fase berikutnya menambah: precache shell saat install, Background Sync untuk
 * outbox laporan, dan handler push notification.
 */

const VERSION = "v1";
const ASSET_CACHE = `eclean-assets-${VERSION}`;
const PAGE_CACHE = `eclean-pages-${VERSION}`;

// Prefix path yang dimiliki Laravel — jangan disentuh service worker.
const LARAVEL_PREFIXES = [
  "/api",
  "/admin",
  "/storage",
  "/keluhan",
  "/auth",
  "/livewire",
  "/sanctum",
  "/vendor",
];

self.addEventListener("install", () => {
  // Tidak auto-skipWaiting: SW baru menunggu sampai user menekan "Perbarui"
  // (lihat handler message SKIP_WAITING) agar tidak memaksa reload saat mengisi form.
});

// Aktifkan SW baru atas perintah halaman (tombol "Perbarui").
self.addEventListener("message", (event) => {
  if (event.data && event.data.type === "SKIP_WAITING") {
    self.skipWaiting();
  }
});

self.addEventListener("activate", (event) => {
  event.waitUntil(
    (async () => {
      // Bersihkan cache versi lama.
      const keys = await caches.keys();
      await Promise.all(
        keys
          .filter((k) => !k.endsWith(VERSION))
          .map((k) => caches.delete(k)),
      );
      await self.clients.claim();
    })(),
  );
});

function isLaravelPath(pathname) {
  return LARAVEL_PREFIXES.some(
    (p) => pathname === p || pathname.startsWith(p + "/"),
  );
}

// Di localhost (dev) jangan ikut campur fetch — biarkan HMR Next bekerja normal.
// Push & notifikasi tetap aktif untuk pengujian.
const IS_LOCALHOST =
  self.location.hostname === "localhost" ||
  self.location.hostname === "127.0.0.1";

self.addEventListener("fetch", (event) => {
  if (IS_LOCALHOST) return;

  const { request } = event;
  if (request.method !== "GET") return;

  const url = new URL(request.url);
  if (url.origin !== self.location.origin) return; // lintas-origin: biarkan
  if (isLaravelPath(url.pathname)) return; // milik Laravel: biarkan jaringan

  // Asset hashed Next.js → CacheFirst (tak pernah berubah utk hash yg sama).
  if (
    url.pathname.startsWith("/_next/static/") ||
    url.pathname.startsWith("/icons/") ||
    url.pathname.endsWith(".woff2")
  ) {
    event.respondWith(cacheFirst(request, ASSET_CACHE));
    return;
  }

  // Navigasi / dokumen HTML → StaleWhileRevalidate (instan + diperbarui).
  if (request.mode === "navigate" || request.destination === "document") {
    event.respondWith(staleWhileRevalidate(request, PAGE_CACHE));
    return;
  }

  // GET same-origin lain → StaleWhileRevalidate ringan.
  event.respondWith(staleWhileRevalidate(request, PAGE_CACHE));
});

async function cacheFirst(request, cacheName) {
  const cache = await caches.open(cacheName);
  const cached = await cache.match(request);
  if (cached) return cached;
  const res = await fetch(request);
  if (res && res.ok) cache.put(request, res.clone());
  return res;
}

async function staleWhileRevalidate(request, cacheName) {
  const cache = await caches.open(cacheName);
  const cached = await cache.match(request);
  const network = fetch(request)
    .then((res) => {
      if (res && res.ok) cache.put(request, res.clone());
      return res;
    })
    .catch(() => cached);
  return cached || network;
}

/* ----- Web Push (VAPID) ----- */

self.addEventListener("push", (event) => {
  let data = {};
  try {
    data = event.data ? event.data.json() : {};
  } catch {
    data = { title: "Apps KopkarYAPI", body: event.data ? event.data.text() : "" };
  }

  const title = data.title || "Apps KopkarYAPI";
  const options = {
    body: data.body || "",
    icon: "/icons/icon-192.png",
    badge: "/icons/icon-192.png",
    data: { url: data.url || "/notifikasi" },
    vibrate: [80, 40, 80],
  };

  event.waitUntil(self.registration.showNotification(title, options));
});

self.addEventListener("notificationclick", (event) => {
  event.notification.close();
  const target = (event.notification.data && event.notification.data.url) || "/notifikasi";

  event.waitUntil(
    (async () => {
      const clientsArr = await self.clients.matchAll({
        type: "window",
        includeUncontrolled: true,
      });
      // Fokuskan tab yang sudah terbuka bila ada.
      for (const client of clientsArr) {
        if ("focus" in client) {
          client.navigate(target).catch(() => {});
          return client.focus();
        }
      }
      if (self.clients.openWindow) return self.clients.openWindow(target);
    })(),
  );
});
