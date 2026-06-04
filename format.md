# Rencana Update Aplikasi E-Clean

## Ringkasan
Update ini mencakup dua hal besar:
1. **Pembuatan versi mobile** menggunakan React Native + Expo (EAS Build).
2. **Penambahan dashboard role baru**: Satpam, Office Boy, dan Petugas Toko.

Aplikasi backend tetap menggunakan Laravel 12 + Filament 4 yang sudah ada. Mobile app akan mengkonsumsi REST API (Laravel Sanctum) dari backend yang sama.

---

## 1. Penambahan Role Baru

### 1.1 Role yang Ditambahkan
| Role | Kode | Fokus Dashboard |
|------|------|------------------|
| Satpam | `satpam` | Patroli, laporan keamanan, log shift, laporan insiden |
| Office Boy | `office_boy` | Tugas harian OB, request konsumsi, laporan kebersihan ruangan |
| Petugas Toko | `petugas_toko` | Stok barang toko, transaksi, laporan harian toko |

> **Wajib lampirkan foto**: Setiap pelaporan yang dibuat oleh Satpam, Office Boy, dan Petugas Toko **wajib menyertakan minimal 1 foto** sebagai bukti. Validasi dilakukan di sisi backend (API & Filament form) dan di sisi mobile (form tidak bisa submit tanpa foto).

> **Penjadwalan**: Jadwal kerja / shift / tugas untuk Satpam, Office Boy, dan Petugas Toko **dibuat oleh Supervisor dan Superadmin** (bukan dibuat sendiri oleh role staff). Role staff hanya melihat jadwal yang ditugaskan kepadanya dan melakukan check-in / pelaporan terhadap jadwal tersebut.

### 1.2 Backend (Laravel + Filament Shield)
- Tambah 3 role baru pada `database/seeders/ShieldSeeder.php` (atau seeder role yang dipakai).
- Buat policy + permission untuk resource yang dipakai per role.
- Tambah panel dashboard khusus per role di Filament (atau gunakan single panel dengan navigation group berbeda).
- Buat resource Filament baru bila diperlukan:
  - `PatroliResource` (Satpam)
  - `LaporanInsidenResource` (Satpam)
  - `TugasOBResource` (Office Boy)
  - `StokTokoResource` + `TransaksiTokoResource` (Petugas Toko)
  - `JadwalSatpamResource`, `JadwalOBResource`, `JadwalTokoResource` — **CRUD hanya untuk Supervisor & Superadmin**; role staff terkait hanya `viewAny` + `view` terhadap jadwal miliknya.
- Tambah migration tabel `jadwal_satpam`, `jadwal_ob`, `jadwal_toko` (struktur mirip `jadwal_kebersihan`: `user_id`, `unit_id`, `tanggal`, `shift`, `lokasi`, `catatan`, `status`).
- Policy: `create/update/delete` hanya untuk role `supervisor` & `super_admin`. Mobile API endpoint `POST/PUT/DELETE` jadwal juga di-gate dengan middleware role yang sama.
- Migration baru untuk tabel pendukung (patroli, insiden, tugas_ob, stok_toko, transaksi_toko).
- Pastikan limit 30 hari (sesuai commit `2b8360b`) juga diterapkan untuk role baru.
- Tambah tabel polymorphic `report_photos` (`id`, `reportable_type`, `reportable_id`, `path`, `created_at`) untuk menyimpan foto laporan dari semua role baru. Foto disimpan di `storage/app/public/reports/{role}/{YYYY-MM}/`.
- Tambah validasi `required|image|max:5120` (mendukung multi-file) pada setiap form/endpoint pelaporan role baru.

### 1.3 Dashboard Widget
- Mengacu pada design di `design/specialized_staff_dashboard_updated/` sebagai base.
- Setiap role punya widget statistik berbeda:
  - **Satpam**: jumlah patroli hari ini, insiden terbuka, shift aktif.
  - **Office Boy**: tugas selesai vs pending, request konsumsi hari ini.
  - **Petugas Toko**: total transaksi hari ini, stok menipis, omzet 7 hari.

---

## 2. Versi Mobile (React Native + Expo / EAS)

### 2.1 Stack Mobile
- **Framework**: React Native (Expo SDK terbaru)
- **Build**: EAS Build (`eas.json` untuk profile development / preview / production)
- **State**: Zustand atau Redux Toolkit
- **Data fetching**: TanStack Query (React Query) + Axios
- **Navigation**: Expo Router (file-based) atau React Navigation
- **UI**: NativeWind (TailwindCSS for RN) agar selaras dengan design HTML
- **Auth**: Laravel Sanctum token (SecureStore untuk simpan token)
- **Push notification**: Expo Push (terintegrasi WhatsApp/Twilio yang sudah ada)

### 2.2 Struktur Folder
```
mobile/
├── app/                  # Expo Router routes
│   ├── (auth)/login.tsx
│   ├── (tabs)/
│   │   ├── index.tsx     # dashboard sesuai role
│   │   ├── tugas.tsx
│   │   ├── laporan.tsx
│   │   └── profile.tsx
├── components/
├── hooks/
├── lib/api.ts            # axios instance + interceptor
├── stores/
├── constants/
├── assets/
├── app.json
├── eas.json
└── package.json
```

### 2.3 Penyesuaian Backend untuk Mobile API
- Tambah folder `routes/api.php` endpoint:
  - `POST /api/auth/login` (Sanctum)
  - `POST /api/auth/logout`
  - `GET  /api/me`
  - `GET  /api/dashboard` (response disesuaikan role)
  - `GET  /api/jadwal-kebersihan`
  - `POST /api/laporan-kegiatan`
  - `GET  /api/patroli` / `POST /api/patroli`
  - `GET  /api/tugas-ob` / `POST /api/tugas-ob/{id}/selesai`
  - `GET  /api/stok-toko` / `POST /api/transaksi-toko`
  - `GET  /api/jadwal-satpam`, `GET /api/jadwal-ob`, `GET /api/jadwal-toko` — semua role bisa lihat jadwal miliknya.
  - `POST/PUT/DELETE` jadwal di atas → **hanya supervisor & superadmin** (middleware `role:supervisor|super_admin`).
  - Semua endpoint `POST` pelaporan (`/api/patroli`, `/api/laporan-insiden`, `/api/tugas-ob/{id}/selesai`, `/api/laporan-toko`, dll.) menerima `multipart/form-data` dengan field `photos[]` (minimal 1, maksimal 5, masing-masing ≤ 5 MB, format JPG/PNG/WEBP). Request tanpa foto akan ditolak `422 Unprocessable Entity`.
- Buat `app/Http/Controllers/Api/*` + `app/Http/Resources/*` (API Resource).
- Tambah middleware role-check (gunakan `spatie/laravel-permission` yang sudah dipakai Shield).

### 2.4 Mapping Design → Screen Mobile
Design HTML pada folder `design/` dikonversi ke komponen React Native:
| Design Folder | Screen Mobile |
|----------------|----------------|
| `login_screen` | `app/(auth)/login.tsx` |
| `staff_dashboard_updated` | Dashboard Petugas Kebersihan |
| `specialized_staff_dashboard_updated` | Dashboard Satpam / OB / Petugas Toko |
| `supervisor_dashboard` | Dashboard Supervisor |
| `stakeholder_dashboard` | Dashboard Pengurus |
| `superadmin_dashboard` | Dashboard Admin (read-only di mobile) |

### 2.5 Mode Hybrid (Offline-First + Auto Sync)

Banyak petugas lapangan memiliki keterbatasan HP / sinyal internet. Aplikasi mobile **harus tetap bisa membuat pelaporan ketika offline**, lalu otomatis sinkron saat koneksi kembali tersedia. Waktu pelaporan tetap memakai waktu **saat user membuat laporan (offline)**, bukan waktu sinkron.

#### 2.5.1 Arsitektur Offline
- **Local DB**: `expo-sqlite` (atau `WatermelonDB` / `Drizzle ORM` untuk query lebih nyaman) — menyimpan draft & antrian sync.
- **File foto**: disimpan di `FileSystem.documentDirectory + 'pending_reports/{uuid}.jpg'` agar tidak hilang saat app di-restart.
- **Network status**: pakai `@react-native-community/netinfo` untuk deteksi online/offline secara realtime.
- **Background sync**: `expo-task-manager` + `expo-background-fetch` agar antrian dikirim walau app tidak dibuka (best-effort di Android, lebih terbatas di iOS).

#### 2.5.2 Skema Tabel Lokal `pending_reports`
| Kolom | Tipe | Keterangan |
|-------|------|------------|
| `id` | TEXT (UUID v4) | Generate di device, jadi `client_id` di backend |
| `endpoint` | TEXT | Contoh `/api/patroli` |
| `payload` | TEXT (JSON) | Field laporan |
| `photos` | TEXT (JSON array path lokal) | Path file di `documentDirectory` |
| `reported_at` | TEXT (ISO 8601) | **Waktu user membuat laporan** — dikirim ke server |
| `created_at` | INTEGER (epoch) | Sama dengan `reported_at`, untuk sorting |
| `status` | TEXT | `pending` / `syncing` / `failed` |
| `attempts` | INTEGER | Retry counter |
| `last_error` | TEXT | Error terakhir saat sync |

#### 2.5.3 Alur Pelaporan
1. User mengisi form → tekan **Simpan/Kirim**.
2. App ambil `reported_at = new Date().toISOString()` (waktu device).
3. Foto disalin ke folder `pending_reports/`.
4. Insert ke tabel `pending_reports` dengan status `pending`.
5. UI langsung tampilkan "Tersimpan — menunggu sinkron" + badge antrian di header.
6. Sync worker jalan ketika:
   - Aplikasi dibuka & online,
   - `NetInfo` mendeteksi koneksi kembali,
   - Background fetch trigger (Android).
7. Saat sync: kirim `multipart/form-data` dengan field tambahan `reported_at` & `client_id`.
8. Jika berhasil → hapus row + foto lokal. Jika gagal → `attempts++`, exponential backoff (1m → 5m → 15m → 1h, max 24 jam). Setelah 10 attempt → status `failed` & user diberi notifikasi untuk retry manual.

#### 2.5.4 Penyesuaian Backend
- Semua endpoint pelaporan **wajib menerima**:
  - `client_id` (UUID dari device) — disimpan unik di kolom `client_id` tiap tabel pelaporan untuk **idempotency** (request yang sama tidak membuat duplikat).
  - `reported_at` (ISO 8601) — disimpan di kolom `reported_at`. Field `created_at` Laravel = waktu insert server (untuk audit), `reported_at` = waktu user membuat laporan (dipakai untuk tampilan & laporan).
- Tambah migration: kolom `client_id` (nullable, unique per tabel) & `reported_at` (datetime) pada `patroli`, `laporan_insiden`, `tugas_ob`, `transaksi_toko`, `laporan_kegiatan`.
- Validasi `reported_at`:
  - Tidak boleh di masa depan > 5 menit (toleransi clock skew).
  - Tidak boleh > 30 hari ke belakang (cegah data sangat lama yang mungkin device-time error).
- Endpoint mengembalikan `409 Conflict` jika `client_id` sudah ada → mobile menganggap sukses & hapus dari antrian (handling duplicate retry).

#### 2.5.5 UX Indikator Offline
- Banner di atas dashboard: "📴 Mode offline — laporan akan disinkron otomatis".
- Badge angka antrian di icon profile/settings.
- Daftar antrian dapat dibuka di menu "Antrian Sinkron" → user bisa lihat status, retry manual, atau hapus.
- Setelah sync berhasil, toast: "✅ N laporan berhasil disinkron".

#### 2.5.6 Data Master untuk Mode Offline
Agar form bisa diisi tanpa internet, data master di-cache lokal saat user login & di-refresh setiap kali online:
- Jadwal milik user (Satpam/OB/Toko/Kebersihan)
- Daftar unit / lokasi
- Daftar kategori insiden, jenis tugas, daftar barang toko
- Profile user

Sync data master memakai endpoint `GET /api/sync/master?since=<timestamp>` (delta sync).

### 2.6 EAS Build Setup
- `eas.json` profile:
  - `development` → APK + dev client
  - `preview` → APK internal testing
  - `production` → AAB Play Store / IPA App Store
- Workflow: `eas build --platform android --profile preview`
- OTA update via `expo-updates` untuk patch UI tanpa rebuild.

---

## 3. Tahapan Pengerjaan

### Fase 1 — Persiapan Backend (1–2 hari)
- [ ] Buat migration & model untuk role baru (patroli, insiden, tugas_ob, stok_toko, transaksi_toko)
- [ ] Tambah kolom `client_id` (unique) & `reported_at` (datetime) pada semua tabel pelaporan
- [ ] Tambah role di Shield + seeder
- [ ] Generate API token Sanctum config (`config/sanctum.php`)
- [ ] Buat API routes + controllers + resources (handle idempotency via `client_id` & terima `reported_at`)
- [ ] Endpoint `GET /api/sync/master` untuk delta sync data master
- [ ] Tulis unit test untuk endpoint utama (termasuk skenario duplicate `client_id` → 409)

### Fase 2 — Dashboard Filament Role Baru (2–3 hari)
- [ ] Resource Filament untuk Satpam, OB, Petugas Toko
- [ ] Resource Jadwal (Satpam/OB/Toko) — CRUD untuk Supervisor & Superadmin, read-only untuk staff
- [ ] Widget statistik per role
- [ ] Policy + permission per role (gate create/update/delete jadwal hanya supervisor & superadmin)
- [ ] Limit 30 hari konsisten

### Fase 3 — Inisialisasi Mobile App (1 hari)
- [ ] `npx create-expo-app mobile`
- [ ] Setup NativeWind, Expo Router, Axios, React Query, Zustand
- [ ] Setup EAS (`eas init`, `eas build:configure`)
- [ ] Setup environment variable (`EXPO_PUBLIC_API_URL`)

### Fase 4 — Implementasi Layar Mobile (5–7 hari)
- [ ] Login screen + auth flow (Sanctum)
- [ ] Dashboard dinamis per role
- [ ] List & detail tugas/jadwal
- [ ] Form laporan kegiatan (dengan kamera + lokasi) — tombol submit **disabled** sampai user melampirkan minimal 1 foto via `expo-image-picker` atau `expo-camera`
- [ ] Preview & hapus foto sebelum submit, kompres otomatis (`expo-image-manipulator`) agar < 2 MB sebelum upload
- [ ] **Offline-first**: integrasi `expo-sqlite` + `NetInfo` + background sync, halaman "Antrian Sinkron", indikator status koneksi
- [ ] Master data cache + delta sync
- [ ] Notifikasi push

### Fase 5 — Testing & Build (2 hari)
- [ ] QA manual semua role
- [ ] Build preview via EAS, distribusi internal
- [ ] Perbaikan bug
- [ ] Build production

---

## 4. Catatan Teknis
- API URL produksi disimpan di `.env` mobile (`EXPO_PUBLIC_API_URL`).
- Token disimpan di `expo-secure-store`, refresh saat expired.
- Untuk upload foto laporan, gunakan `expo-image-picker` + multipart ke endpoint Laravel.
- Geolocation patroli menggunakan `expo-location` (akurasi tinggi).
- Pastikan CORS di Laravel mengizinkan domain mobile (gunakan `*` untuk dev, whitelist di prod).
- Versi mobile mengikuti semantic versioning dan otomatis bump via EAS.

---

## 5. Deliverable
1. Branch `feature/role-baru` — backend role Satpam / OB / Petugas Toko + API.
2. Folder `mobile/` — source code React Native + konfigurasi EAS.
3. Build APK preview untuk testing internal.
4. Dokumentasi API (`docs/api.md`) dan panduan build mobile (`docs/mobile.md`).
