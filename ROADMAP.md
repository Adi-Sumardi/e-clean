# Peta Perjalanan Aplikasi E-Clean (Clean Service System)

Dokumen ini menjelaskan peta perjalanan pengembangan aplikasi **E-Clean** — sistem manajemen layanan kebersihan berbasis Laravel + Filament — mulai dari tahap perencanaan, riwayat update yang sudah dirilis, hingga rencana update yang akan datang.

Penomoran bulan diambil dari riwayat `git commit` dan `push` ke GitHub pada repository ini.

---

## 1. Tahap Perencanaan

Sebelum baris kode pertama ditulis, aplikasi dirancang untuk memenuhi kebutuhan operasional layanan kebersihan dengan tujuan:

- **Digitalisasi laporan kegiatan kebersihan** petugas di lapangan.
- **Monitoring real-time** oleh supervisor dan pengurus terhadap kinerja petugas.
- **Akuntabilitas dengan bukti foto** ber-watermark (waktu + lokasi).
- **Layanan keluhan tamu (guest complaint)** yang terintegrasi langsung dengan jadwal petugas.
- **Manajemen jadwal kebersihan** berbasis unit/lokasi dengan QR Code.
- **Notifikasi WhatsApp** untuk eskalasi keluhan dan informasi penting.
- **Pelaporan bulanan** dalam bentuk PDF untuk kebutuhan manajemen.

Stack teknologi yang dipilih:
- **Backend:** Laravel + Filament v3
- **Database:** MySQL (development) → PostgreSQL (production)
- **Cache & Queue:** Redis
- **Web Server:** Nginx
- **Frontend interaktif:** Livewire + Alpine.js
- **Peta:** Leaflet + OpenStreetMap
- **Mobile (rencana):** React Native (folder [mobile/](mobile/))

---

## 2. Riwayat Update (Berdasarkan Bulan Push ke GitHub)

### November 2025 — Fondasi Aplikasi
- Inisialisasi project E-Clean berbasis Laravel.
- Dokumentasi awal project.
- Fitur **GPS-enabled watermark camera** untuk Activity Reports.
- Halaman **ViewActivityReport** agar semua role dapat melihat foto laporan kegiatan.

### Desember 2025 — Persiapan Produksi & Fitur Inti
- Pre-deployment fixes dan setup produksi.
- Konfigurasi **Nginx + deployment guide** untuk domain `eclean.adilabs.id`.
- Banyak iterasi perbaikan (auto-commit) untuk stabilisasi awal.
- Hak akses **petugas** untuk membuat Activity Reports.
- Field kamera membaca `lokasi_id` secara reaktif dari Livewire state.
- Script deployment VPS dengan **PostgreSQL + Redis**.
- Perbaikan CSP header untuk Alpine.js / Filament / Leaflet / OpenStreetMap.
- Eksperimen **GPS coordinate + map picker** untuk Lokasi (akhirnya dihapus karena kompleksitas).
- Dukungan **multiple photos (maks 5)** pada laporan kegiatan.
- Hak akses **supervisor** untuk mengelola data Lokasi.
- Penambahan **Unit management**, **Guest Complaint system**, dan enum **WorkShift**.
- Halaman **404 ramah pengguna** untuk kode lokasi tidak valid.
- **Notifikasi WhatsApp** keluhan tamu via Fonnte → diganti **WatZap**.
- Penambahan informasi Unit pada semua notifikasi WhatsApp.
- Rebranding ke **Clean Service System** dengan logo baru.
- Penggantian **Barcode → QR Code** (PNG → SVG), auto-generate saat lokasi dibuat.
- Filter **Unit** untuk pemilihan Lokasi di JadwalKebersihan.
- Kolom **Unit** pada tabel jadwal kebersihan dan Activity Reports.
- **Auto-assign** keluhan tamu ke petugas yang sedang dijadwalkan.
- Status pelaporan **On-Time / Late / Expired** untuk Activity Reports.
- Widget **ReportingStatusWidget** di dashboard.
- Reorganisasi navigasi: grup **Master Data** dan **Monitoring**.

### Januari 2026 — Stabilisasi & Integrasi Keluhan Tamu
- Perbaikan konfigurasi **timezone** dan pesan dashboard.
- Script deploy otomatis dengan cache clearing.
- Fitur **camera selection** dan **timer** pada watermark camera.
- Perbaikan beberapa bug mobile: tombol Cancel, timer 10 detik, hapus foto.
- Perbaikan bug **rate limiting**, form image, dan validasi nomor HP pada keluhan tamu.
- Integrasi keluhan tamu dengan widget petugas (filter by `assigned_to` + jadwal hari ini).
- Dropdown **petugas assignment** pada form keluhan tamu.
- Tampilkan keluhan tamu di form Activity Report saat lokasi dipilih.
- Auto-update status keluhan tamu berdasarkan status Activity Report.
- Infolist view **GuestComplaint** dengan foto dari ActivityReport.
- Validasi `jam_selesai` wajib pada form ActivityReport.
- Aktifkan indikator field wajib (asterisk merah) di form Filament.

### Februari 2026 — Pelaporan Bulanan & Penyederhanaan GPS
- Halaman **Laporan Bulanan** dengan export PDF dan widget dashboard.
- Filter pencarian (searchable), logika **petugas-unit dependent**, dan tampilan PDF yang lebih baik.
- Redesign filter section dengan native select dan layout yang lebih rapi.
- Tombol **Download PDF** dengan inline style fallback.
- **GPS dihilangkan** dari watermark kamera — lokasi diambil dari dropdown.
- Migrasi membuat kolom GPS pada tabel `photo_metadata` jadi nullable.
- Perbaikan **Google OAuth** yang diblokir oleh Service Worker PNA check.

### Maret 2026 — Stabilisasi Besar & Testing
- Perbaikan **13+ bug** sekaligus.
- Penambahan shift **standby** dan **sweeping**.
- Penambahan **131 unit test**.
- Penghapusan 42 dokumen yang tidak digunakan.
- Script **`update.sh`** untuk update produksi.
- Migrasi shift kompatibel dengan **PostgreSQL** (produksi).
- Perbaikan tombol Cancel yang tidak sengaja men-submit form pada halaman buat laporan kegiatan.

### April 2026 — Pembatasan Data & Provider WhatsApp Baru
- Filter **Unit** pada Jadwal Kebersihan.
- Pembatasan data dashboard reports menjadi **30 hari terakhir**.
- Penambahan provider WhatsApp **Twilio**.
- Pembatasan seluruh resource data menjadi **30 hari** untuk role supervisor / petugas / pengurus (peningkatan performa & relevansi data).

---

## 3. Rencana Update yang Akan Datang

Rencana berikut adalah arah pengembangan ke depan, belum di-push ke GitHub.

### Aplikasi Mobile (React Native)
- Folder [mobile/](mobile/) sudah dipersiapkan dan komponen seperti [TaskCard.tsx](mobile/components/TaskCard.tsx) menjadi pondasinya.
- Target: aplikasi petugas berbasis mobile native untuk laporan kegiatan, push notification, dan offline-first.

### Penyempurnaan Notifikasi
- Multi-provider WhatsApp (WatZap + Twilio + Fonnte) dengan **failover otomatis**.
- Channel notifikasi tambahan: Email & In-app notification.

### Dashboard & Analitik
- Dashboard analitik tingkat **pengurus / manajemen** dengan tren bulanan & per-unit.
- Indikator KPI petugas (on-time rate, jumlah keluhan tertangani, dsb).

### Manajemen Pengguna & Keamanan
- **2FA / OTP login** opsional.
- Audit log aktivitas pengguna.
- Penyempurnaan role & permission granular.

### Operasional & Performa
- **Queue worker** untuk pemrosesan foto & notifikasi WhatsApp.
- Penyimpanan foto ke **object storage** (S3-compatible) untuk skalabilitas.
- Image compression otomatis sebelum upload.

### Pelaporan
- Export laporan tambahan: **Excel** dan **CSV**.
- Laporan kustom dengan filter lanjutan (rentang tanggal bebas, multi-unit, multi-petugas).

### Kualitas Kode
- Penambahan **feature test** Filament resource selain unit test.
- Integrasi **CI/CD GitHub Actions** untuk test otomatis pada setiap PR.
- Static analysis dengan **PHPStan / Larastan**.

---

## 4. Ringkasan Timeline

| Bulan        | Fokus Utama                                              |
|--------------|----------------------------------------------------------|
| Nov 2025     | Fondasi project + watermark camera                       |
| Des 2025     | Setup produksi, Unit/Guest Complaint, QR Code, branding  |
| Jan 2026     | Stabilisasi, integrasi keluhan tamu ke laporan kegiatan  |
| Feb 2026     | Laporan bulanan PDF, simplifikasi GPS                    |
| Mar 2026     | Stabilisasi besar, unit test, dukungan PostgreSQL        |
| Apr 2026     | Pembatasan data 30 hari, provider WhatsApp Twilio        |
| Mendatang    | Mobile app, multi-provider failover, analitik, CI/CD     |

---

_Dokumen ini akan terus diperbarui mengikuti perkembangan aplikasi._
