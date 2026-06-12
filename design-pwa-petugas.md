# Desain: PWA (Next.js) Pengganti Filament — Push Notification + Offline-First

> Status: **Disepakati — setup dimulai** · Target: **mengganti SELURUH UI Filament dengan Next.js secara bertahap** (Filament akhirnya dihapus).
> Backend Laravel + API Sanctum **tetap dipakai** sebagai sumber data; hanya ditambah beberapa endpoint.
> **Urutan rollout: petugas → supervisor → superadmin.** Selama transisi, Filament & PWA hidup berdampingan; tiap role dipindah, menu Filament-nya dipensiunkan, sampai akhirnya `/admin` dihapus total.

---

## 1. Tujuan & Cakupan

**Masalah:** Petugas mengeluh Filament berat (banyak JS, full page reload, kurang cocok di HP murah / sinyal jelek).

**Solusi:** Aplikasi **PWA Next.js** ringan khusus petugas yang:
1. Ringan & cepat di HP kelas bawah (app shell di-cache, navigasi instan).
2. **Offline-first** — petugas bisa lihat jadwal & *submit laporan tanpa internet*; tersimpan lokal lalu **auto-sync** saat online.
3. **Web Push (VAPID)** — notifikasi laporan disetujui/ditolak, jadwal baru, keluhan tamu masuk.

**Cakupan akhir = SELURUH fungsi Filament**, dipindah bertahap per role:

| Tahap | Audiens | Fungsi yang dipindah ke PWA | Status API |
|---|---|---|---|
| **1** | Petugas (4 role lapangan) | Login, jadwal hari ini/akan datang, submit laporan (offline + foto), riwayat & status laporan, penilaian diri, notifikasi, profil | ✅ sudah ada |
| **2** | Supervisor | Inbox laporan masuk, **approve/reject** + alasan, monitoring per lokasi/unit, leaderboard, penilaian petugas | ✅ approve/reject sudah ada; sebagian list perlu filter |
| **3** | Superadmin/admin | Master data CRUD (user, lokasi, unit, jadwal), settings, export PDF, laporan bulanan, kelola keluhan tamu, roles/permissions | ⚠️ sebagian ada (lokasi/users/units/jadwal), sebagian perlu ditambah (settings, export, laporan bulanan) |

Setelah ketiga tahap selesai → **hapus Filament & `/admin`**, Laravel jadi API-only.

**Tahap 1 — Pengguna petugas = 4 role lapangan** (domain ditentukan oleh role):

| Role | Domain | Endpoint jadwal | Endpoint laporan |
|---|---|---|---|
| `petugas` | Kebersihan | `/api/v1/jadwal/*` | `/api/v1/activity-reports` |
| `satpam` | Keamanan | `/api/v1/satpam/jadwal/*` | `/api/v1/satpam/laporan` |
| `office_boy` | Office Boy | `/api/v1/office-boy/jadwal/*` | `/api/v1/office-boy/laporan` |
| `petugas_toko` | Toko | `/api/v1/toko/jadwal/*` | `/api/v1/toko/laporan` |

PWA membaca role dari `/api/v1/auth/me`, lalu memilih set endpoint + label yang sesuai (satu basis kode, dipetakan lewat config domain).

---

## 2. Arsitektur & Deployment

### Topologi — satu domain `css.kopkaryapi.id` (tanpa subdomain baru)
```
   Petugas (HP)        ┌─────────────────────────────────────────────────┐
   browser/PWA ──────► │  css.kopkaryapi.id  (Laravel + PWA, satu origin) │
   Admin browser ────► │                                                 │
                       │   /            → PWA Next.js (static, out/)  ◄── petugas
                       │   /admin       → Filament (tetap)            ◄── admin/supervisor
                       │   /api/v1/*    → Sanctum API (dipakai PWA)       │
                       │   /keluhan/*   → form keluhan tamu (tetap)       │
                       │                                                 │
                       │   fetch ke /api/v1 = same-origin → TANPA CORS    │
                       └─────────────────────────────────────────────────┘
```

### Keputusan deployment
- **Tanpa subdomain baru.** PWA dipasang di **root `css.kopkaryapi.id/`** (jadi wajah utama domain). Filament tetap di `/admin`, API tetap di `/api/v1`. Karena PWA & API **satu origin → tidak perlu CORS, tidak perlu DNS/SSL baru, tidak perlu vhost kedua**. Cukup tambah `location` block di nginx yang sudah ada.
- **Tidak pakai SSR.** SSR berguna untuk SEO & konten publik. App petugas seluruhnya di belakang login + offline-first, jadi SSR tidak relevan dan malah menyulitkan caching. Kita pakai **`next export` (static / SPA mode)**: hasilnya kumpulan file statis (`out/`) yang ringan, tanpa proses Node berjalan.
- **Nginx:** `try_files` ke file statis PWA untuk root, dengan pengecualian path Laravel (`/admin`, `/api`, `/keluhan`, `/auth`, `/storage`, `/livewire`) tetap diteruskan ke PHP-FPM. Root yang sekarang `redirect('/admin/login')` (di `web.php`) dihapus/diganti agar `/` melayani PWA.
- **Service worker scope:** karena PWA di root, SW menguasai seluruh origin — `fetch` handler **harus mengabaikan** path Laravel (`/admin`, `/api`, `/storage`, dst.) agar tidak meng-cache/ganggu Filament. Diatur lewat allowlist navigasi di SW.
- **Auth:** pakai **Bearer token** (Sanctum personal access token) — sama seperti mobile. Walau kini same-origin (cookie SPA secara teknis bisa), token tetap dipilih agar konsisten dengan API existing & lebih sederhana untuk outbox offline. Token disimpan di IndexedDB.

### Stack frontend
- **Next.js (App Router, output: 'export')** + **TypeScript**
- **Tailwind CSS** (konsisten dgn mobile yang sudah pakai NativeWind/Tailwind)
- **TanStack Query** (caching + offline persistence — sudah dipakai di mobile, polanya bisa diport)
- **Workbox** (service worker: precache app shell + runtime cache API GET)
- **idb** (wrapper IndexedDB untuk outbox laporan + cache data)
- **next-pwa** atau manifest+SW manual (lihat §5)

---

## 3. Struktur Aplikasi & Layar

```
app/
  (auth)/login                     # form login → simpan token
  (app)/
    beranda/                       # ringkasan: jadwal hari ini, status sync, notif terbaru
    jadwal/                        # daftar jadwal (today / upcoming), per-domain
    jadwal/[id]                    # detail jadwal → tombol "Buat Laporan"
    laporan/baru?jadwal=...        # FORM LAPORAN (kamera, multi-foto, catatan) ← inti offline
    laporan/                       # riwayat laporan + status (pending/approved/rejected)
    laporan/[id]                   # detail + alasan reject bila ada
    penilaian/                     # skor/penilaian petugas
    notifikasi/                    # feed dari /api/v1/notifications
    profil/                        # profil, ganti foto, logout, toggle push
components/
  CameraCapture, PhotoGrid, SyncStatusBar, OfflineBanner, ReportForm, BottomNav
lib/
  api.ts            # fetch wrapper + Bearer + ApiError (port dari mobile/lib/api.ts)
  domain.ts         # mapping role → endpoint/label
  outbox.ts         # IndexedDB queue (port dari mobile/lib/offline-queue.ts)
  sync.ts           # proses sync + idempotency
  push.ts           # subscribe/unsubscribe web push
  db.ts             # skema IndexedDB (idb)
  auth.ts           # token store
```

**Navigasi:** bottom-nav 4–5 ikon (Beranda, Jadwal, Laporan, Notifikasi, Profil) — pola mobile-first, tap-friendly.

---

## 4. Desain Offline-First

### Prinsip
- **Read (jadwal, profil, penilaian):** strategi *stale-while-revalidate*. Data terakhir disimpan di IndexedDB lewat persistence TanStack Query → langsung tampil saat offline, di-refresh saat online.
- **Write (submit laporan):** *outbox pattern*. Submit selalu masuk antrian lokal dulu; UI langsung kasih feedback "tersimpan, menunggu sync".

### Outbox (IndexedDB)
Foto dari kamera disimpan sebagai **Blob** di IndexedDB (bukan object URL yang hilang saat reload). Tiap job laporan:

```ts
type OutboxJob = {
  id: string;              // uuid lokal
  idempotencyKey: string;  // dikirim ke server utk anti-duplikat
  domain: 'kebersihan'|'satpam'|'ob'|'toko';
  endpoint: string;        // resolved dari domain
  fields: Record<string, unknown>;  // jadwal_id, catatan, lat/lng, waktu, dll
  photos: Blob[];          // foto kamera
  createdAt: number;
  attempts: number;
  status: 'pending'|'syncing'|'failed';
  lastError?: string;
};
```

### Alur sync
1. User submit → job ditulis ke outbox (status `pending`) → UI tampil "✓ tersimpan offline".
2. Trigger sync ketika: **(a)** event `online`, **(b)** app dibuka/foreground, **(c)** **Background Sync API** (`sync` event di SW — jalan walau app ditutup), **(d)** tombol "Sync sekarang" manual.
3. Untuk tiap job: bangun `FormData` (fields + photos sebagai file) → `POST` dengan header `Idempotency-Key`.
   - **Sukses (2xx):** hapus job, update cache riwayat laporan.
   - **Error transport (offline / status undefined):** biarkan `pending`, retry nanti (backoff).
   - **Error validasi server (4xx):** tandai `failed`, tampilkan ke user untuk diperbaiki (jangan retry membabi buta).
4. **SyncStatusBar** menampilkan jumlah pending + status; **OfflineBanner** muncul saat `navigator.onLine === false`.

> Pola ini meniru `mobile/lib/offline-queue.ts` yang sudah terbukti (deteksi `ApiError.status === undefined` = offline). Kita port konsepnya: AsyncStorage → IndexedDB, file URI → Blob.

### Idempotency (penting — cegah laporan dobel)
Retry bisa terjadi setelah server sebenarnya sudah menerima (mis. respons hilang di jaringan jelek). Solusi: setiap job punya `Idempotency-Key` unik. Backend menyimpan key→report_id; jika key sudah ada, kembalikan report yang sama, bukan buat baru. (Detail backend di §6.)

---

## 5. PWA Shell & Caching (Workbox)

- **manifest.webmanifest**: nama, ikon (sudah ada generator `generate-pwa-icons.php` — reuse), `display: standalone`, theme color, `start_url: /beranda`. → installable "Add to Home Screen".
- **Service worker** (Workbox):
  - *Precache* app shell (HTML/JS/CSS hasil `next export`) → buka instan & jalan offline.
  - *Runtime cache* GET API (jadwal, penilaian) — `NetworkFirst` dengan fallback cache.
  - *Background Sync* untuk outbox (queue plugin Workbox atau handler `sync` manual).
  - *Push* handler (lihat §7).
- **Update strategy:** SW baru → tampilkan toast "Versi baru tersedia, ketuk untuk refresh" (hindari update paksa saat user sedang isi form).

---

## 6. Perubahan Backend (Laravel) — minimal

| # | Perubahan | Detail |
|---|---|---|
| B1 | ~~CORS~~ | **Tidak perlu** — PWA & API satu origin (`css.kopkaryapi.id`). |
| B2 | **Idempotency laporan** | Tabel `report_idempotency_keys(key, user_id, report_type, report_id, created_at)`. Middleware/cek di `store` activity-report + 3 laporan field: jika `Idempotency-Key` header sudah ada → kembalikan report lama (200) alih-alih buat baru. |
| B3 | **Web Push subscription** | Tabel `web_push_subscriptions(id, user_id, endpoint UNIQUE, public_key, auth_token, content_encoding, user_agent, created_at)`. Satu user bisa banyak device. |
| B4 | **Endpoint subscription** | `POST /api/v1/auth/web-push-subscription` (simpan), `DELETE /api/v1/auth/web-push-subscription` (hapus by endpoint). |
| B5 | **Web Push service** | `composer require minishlink/web-push` + generate **VAPID keys** (simpan di `.env`: `VAPID_PUBLIC_KEY`, `VAPID_PRIVATE_KEY`, `VAPID_SUBJECT`). Buat `WebPushService`. |
| B6 | **Kirim notifikasi via Web Push** | Saat event (report approved/rejected, jadwal baru, complaint assigned): kirim ke **semua Web Push subscription** user. Subscription invalid (404/410) dihapus otomatis. |
| B7 | **Pensiunkan Expo** | Endpoint & kolom `expo_push_token` + `ExpoPushService` tidak dipakai lagi (fokus PWA). Boleh dibiarkan dahulu (dead code) lalu dibersihkan, atau dihapus sekalian — tidak ada konsumen baru. |

> Catatan: composer di repo ini perlu `--ignore-platform-req=php` (lihat memory). VAPID public key di-expose ke frontend lewat endpoint config kecil (`GET /api/v1/config/vapid-public-key`) atau di-inject saat build Next.js.

**Tidak ada perubahan** pada skema laporan/jadwal/penilaian — endpoint existing sudah cukup.

---

## 7. Web Push (VAPID) — alur

**Frontend (saat user aktifkan notif di Profil):**
1. `Notification.requestPermission()`.
2. `registration.pushManager.subscribe({ userVisibleOnly: true, applicationServerKey: VAPID_PUBLIC_KEY })`.
3. `POST /auth/web-push-subscription` dgn `{endpoint, keys:{p256dh, auth}}`.

**Backend (saat ada event):**
- Ambil semua subscription user → `WebPushService->send(sub, title, body, data)` via `minishlink/web-push`.
- Subscription invalid (404/410) → hapus dari DB (cleanup otomatis).

**Service worker:**
```js
self.addEventListener('push', e => {
  const d = e.data.json();
  e.waitUntil(self.registration.showNotification(d.title, {
    body: d.body, icon: '/icons/192.png', data: d.url
  }));
});
self.addEventListener('notificationclick', e => {
  e.notification.close();
  e.waitUntil(clients.openWindow(e.notification.data || '/notifikasi'));
});
```

**Catatan iOS:** Web Push di iOS Safari hanya jalan jika PWA **di-install ke home screen** (iOS 16.4+). Perlu instruksi onboarding "Tambah ke Layar Utama" untuk pengguna iPhone.

---

## 8. Rencana Implementasi (bertahap)

Rollout per role: **Tahap 1 Petugas → Tahap 2 Supervisor → Tahap 3 Superadmin → hapus Filament**. Tahap 1 dipecah jadi Fase 0–4 (fondasi sekali untuk semua tahap berikutnya).

### TAHAP 1 — Petugas

**Fase 0 — Fondasi (dikerjakan sekarang)**
- Scaffold Next.js (`output: export`) + TypeScript + Tailwind + TanStack Query di folder `web/`.
- `lib/api.ts` (port dari `mobile/lib/api.ts`), `lib/auth.ts` (token store), `lib/domain.ts` (role→endpoint).
- Layout app-shell + bottom-nav, halaman login, route guard.
- Manifest PWA + registrasi service worker kosong.
- Atur nginx: root → file statis PWA, path Laravel (`/admin`,`/api`,`/storage`,`/keluhan`,`/auth`,`/livewire`) tetap ke PHP-FPM; hapus redirect `/`→`/admin`. Uji login same-origin.
- Backend: B3/B4 tabel+endpoint subscription, B5 VAPID & `WebPushService` (disiapkan, dipakai di Fase 3).

**Fase 1 — Read online**: Beranda, Jadwal (today/upcoming per domain), detail jadwal, Riwayat laporan, Penilaian, Notifikasi.

**Fase 2 — Submit + Offline**: Form laporan + kamera/multi-foto + kompresi (§11). IndexedDB (`db.ts`), `outbox.ts`, `sync.ts`, idempotency (B2). SW precache + Background Sync. SyncStatusBar + OfflineBanner.

**Fase 3 — Web Push**: `push.ts` subscribe/unsubscribe, toggle di Profil, SW push/click handler. B6 kirim notif via Web Push; B7 pensiunkan Expo.

**Fase 4 — Polish & rilis petugas**: update toast SW, install prompt (A2HS), onboarding iOS, uji lapangan. Pensiunkan menu petugas di Filament.

### TAHAP 2 — Supervisor
- Inbox laporan masuk + **approve/reject** (API sudah ada), filter per lokasi/unit, leaderboard, penilaian petugas.
- Reuse fondasi & komponen Tahap 1; supervisor offline tidak kritikal (utamanya online).
- Pensiunkan menu supervisor di Filament.

### TAHAP 3 — Superadmin/Admin
- Master data CRUD (user, lokasi, unit, jadwal), settings, kelola keluhan tamu, roles/permissions, export PDF, laporan bulanan.
- Lengkapi endpoint API yang belum ada (settings, export, laporan bulanan) — lihat §6 (akan ditambah saat tahap ini).
- Setelah lengkap & diverifikasi → **hapus panel Filament & route `/admin`**, Laravel jadi API-only.

---

## 9. Risiko & Mitigasi

| Risiko | Mitigasi |
|---|---|
| Laporan dobel saat retry | Idempotency-Key (B2) |
| Foto besar bikin storage HP penuh / upload lama | Kompres di client (lihat §11) sebelum masuk outbox |
| iOS push terbatas | Onboarding A2HS; fallback in-app notif feed tetap ada |
| Token kadaluarsa saat offline lama | Saat sync 401 → simpan job, minta re-login, lanjut sync setelah login |
| Watermark/metadata kamera (lat/lng/waktu) yang sekarang dibuat server | Tetap kirim koordinat+timestamp dari client; server menempel watermark seperti sekarang (`WatermarkCameraService`) |
| Dua frontend (Filament admin + PWA) divergen | Kontrak API jadi sumber kebenaran; dokumentasikan via Scramble (sudah terpasang) |

---

## 10. Keputusan final (semua sudah disepakati)
- **Cakupan:** **ganti total Filament dengan Next.js**, bertahap **petugas → supervisor → superadmin**, lalu Filament dihapus.
- **Deployment:** **root `css.kopkaryapi.id`** (tanpa subdomain), Next.js static export, **tanpa SSR**, **tanpa CORS** (same-origin). Filament tetap `/admin`.
- **Push:** **Web Push VAPID saja** — Expo dipensiunkan.
- **Foto:** **dikompres di client** (resize + quality, di Web Worker) sebelum upload/outbox. Detail §12.

→ Semua keputusan kunci sudah lengkap; desain siap dieksekusi mulai **Fase 0**.

## 11. Kompresi Foto (detail §10)

**Tujuan:** ukuran file kecil, tapi tampilan di layar praktis tak berubah, dan tidak membebani UI.

- **Library:** `browser-image-compression` — berjalan di **Web Worker** (`useWebWorker: true`) sehingga proses kompresi tidak nge-freeze form saat petugas mengisi.
- **Setelan default (visually-lossless untuk foto bukti):**
  ```ts
  await imageCompression(file, {
    maxWidthOrHeight: 1920,   // sisi terpanjang; cukup tajam di layar, foto HP aslinya 4000px+
    initialQuality: 0.8,      // beda visual ~nol
    maxSizeMB: 0.5,           // target plafon ~500 KB
    useWebWorker: true,
    fileType: 'image/jpeg',   // atau 'image/webp' (lebih kecil ~25-30%, didukung browser modern)
    preserveExif: false,      // koordinat/timestamp dikirim terpisah sbg field, bukan dari EXIF
  });
  ```
- **Hasil tipikal:** 3–5 MB → ~200–400 KB (turun ~90%), tampak identik di layar HP.
- **Alur:** kamera/galeri → kompres (worker) → simpan **Blob** ke outbox IndexedDB → upload saat sync.
- **Watermark lat/lng/waktu** tetap ditempel **server** (`WatermarkCameraService`) seperti sekarang; client tetap mengirim koordinat + timestamp sebagai field terpisah. Kompresi client tidak mengganggu ini.
- **Catatan jujur:** "ukuran kecil + kualitas 100% identik bit-per-bit" tidak mungkin; yang kita capai adalah **tak terlihat bedanya oleh mata** untuk keperluan foto bukti. Angka di atas bisa di-tune bila ada kebutuhan detail lebih tinggi.
