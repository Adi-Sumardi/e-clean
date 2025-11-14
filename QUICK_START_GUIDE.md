# E-Cleaning Service - Quick Start Guide

## Overview

Aplikasi E-Cleaning Service Management System telah berhasil diimplementasikan dengan fitur-fitur berikut:

### ✅ Completed Features (Phase 1-7)

- **Phase 1:** Database Migrations - Struktur database lengkap (Users, Lokasi, Jadwal, Activity Reports, dll)
- **Phase 2:** Filament Resources - CRUD interface untuk semua entitas
- **Phase 3:** Role & Permission - 5 role (Super Admin, Admin, Supervisor, Pengurus, Petugas)
- **Phase 4:** Dashboard Widgets - Real-time statistics dan charts
- **Phase 5:** Image Processing - Automatic WebP compression untuk foto laporan
- **Phase 6:** QR Code System - Generate & scan QR code untuk lokasi
- **Phase 7:** WhatsApp Notifications - Integrasi Fonnte untuk notifikasi

---

## Login & Access

### Default Admin Account

```
URL: http://localhost:8000/admin
Email: admin@ecleaning.test
Password: password
```

### User Roles & Permissions

| Role | Akses |
|------|-------|
| **Super Admin** | Full access ke semua fitur |
| **Admin** | CRUD semua resources |
| **Supervisor** | Approve laporan, manage jadwal, buat penilaian |
| **Pengurus** | Read-only access ke semua data |
| **Petugas** | Buat laporan & presensi, view jadwal |

---

## Main Features Guide

### 1. Dashboard

**Akses:** [http://localhost:8000/admin](http://localhost:8000/admin)

**Widgets:**
- **Stats Overview:** 6 kartu statistik real-time
  - Total Lokasi Aktif
  - Total Petugas Aktif
  - Laporan Hari Ini
  - Presensi Hari Ini
  - Laporan Pending
  - Jadwal Bulan Ini

- **Activity Report Chart:** Line chart dengan filter
  - Filter periode: Today, Week, Month, Year
  - Filter by Petugas
  - Filter by Lokasi

- **Petugas Performance Chart:** Bar + Line chart
  - Top 10 petugas by jumlah laporan
  - Average rating per petugas

### 2. Lokasi Management

**Akses:** Admin Panel → Lokasi

**Features:**
- CRUD lokasi (Kelas, Toilet, Kantor, dll)
- Kode lokasi otomatis (format: LT1-A01)
- Status aktif/non-aktif
- **Print QR Codes** - Cetak semua QR code sekaligus

**Print QR Codes:**
1. Klik tombol "Print QR Codes" di header
2. QR codes akan ditampilkan dalam grid 3 kolom
3. Klik Print atau tekan Ctrl/Cmd + P
4. Tempel QR code di lokasi yang sesuai

### 3. QR Code Scanner

**Akses:** Admin Panel → Tools → Scan QR Code

**Cara Penggunaan (Mobile/Tablet):**
1. Buka halaman "Scan QR Code" di browser mobile
2. Berikan izin akses kamera
3. Pilih kamera (back camera direkomendasikan)
4. Klik "Mulai Scan"
5. Arahkan kamera ke QR code
6. Scanner akan otomatis detect dan tampilkan data lokasi
7. Klik "Buat Laporan" untuk create activity report

**Use Case:**
- Petugas scan QR code saat mulai bersih-bersih
- Otomatis create laporan dengan lokasi ter-isi
- Upload foto before/after
- Submit laporan

### 4. Jadwal Kebersihan

**Akses:** Admin Panel → Jadwal Kebersihan

**Features:**
- Assign petugas ke lokasi dengan tanggal
- Jam mulai & selesai
- Status: Scheduled, In Progress, Completed, Cancelled
- Batch import (jika perlu)

**Workflow:**
1. Admin/Supervisor buat jadwal
2. Petugas dapat notifikasi WhatsApp (jika configured)
3. Petugas check jadwal di panel
4. Scan QR code lokasi
5. Buat activity report

### 5. Activity Reports (Laporan Kegiatan)

**Akses:** Admin Panel → Laporan Kegiatan

**Fields:**
- Lokasi (bisa dari QR scan)
- Petugas (auto dari user login)
- Tanggal & waktu
- Foto sebelum/sesudah (auto-compressed ke WebP)
- Catatan
- Status: Pending, Approved, Rejected

**Approval Workflow:**
1. Petugas submit laporan (status: Pending)
2. Supervisor review laporan
3. Approve/Reject dengan catatan
4. Petugas dapat notifikasi WhatsApp

### 6. Presensi (Attendance)

**Akses:** Admin Panel → Presensi

**Features:**
- Clock in/out dengan foto selfie
- GPS coordinates (coming in Phase 8)
- Status: Hadir, Izin, Sakit, Tanpa Keterangan
- Auto calculate jam kerja

### 7. Penilaian (Evaluations)

**Akses:** Admin Panel → Penilaian

**Features:**
- Supervisor buat penilaian untuk petugas
- Rating 1-5 untuk berbagai aspek:
  - Kualitas kerja
  - Ketepatan waktu
  - Kerapihan
  - Kedisiplinan
- Catatan penilaian
- View history penilaian

---

## WhatsApp Notifications Setup

### 1. Register Fonnte Account

1. Kunjungi [https://fonnte.com](https://fonnte.com)
2. Register dan verifikasi akun
3. Connect nomor WhatsApp Anda
4. Copy API Token dari dashboard

### 2. Configure di Laravel

Edit file `.env`:

```env
FONNTE_TOKEN=your_actual_token_here
```

### 3. Add Phone Numbers ke Users

Pastikan semua user memiliki nomor telepon:

```
Admin Panel → Users → Edit User → Phone Field
Format: 081234567890 (tanpa +62)
```

### 4. Available Notification Templates

System telah menyediakan template untuk:
- New schedule assignment
- Schedule reminder (1 hari sebelum)
- New report notification
- Report approved/rejected
- Attendance reminder (pagi & sore)
- Performance evaluation
- Weekly performance summary
- Late attendance warning

**Implementasi Auto-Send:** Lihat [PHASE_6_7_SUMMARY.md](PHASE_6_7_SUMMARY.md)

---

## Image Upload & Compression

Semua foto yang di-upload (activity reports, presensi) akan otomatis:
- Dikonversi ke format WebP
- Diresize maksimal 1920x1920px
- Dikompres dengan quality 80%
- Menghemat ~80% ukuran file

**Supported Formats:** JPG, JPEG, PNG (auto-convert to WebP)

---

## Common Workflows

### Workflow 1: Daily Cleaning Routine

1. **Admin/Supervisor:** Buat jadwal harian untuk petugas
2. **System:** Kirim notifikasi WhatsApp ke petugas (otomatis)
3. **Petugas:** Check jadwal di panel atau dari notifikasi
4. **Petugas:** Clock in (presensi) dengan foto selfie
5. **Petugas:** Pergi ke lokasi, scan QR code
6. **Petugas:** Foto before, lakukan cleaning, foto after
7. **Petugas:** Submit activity report
8. **Supervisor:** Review & approve/reject laporan
9. **System:** Kirim notifikasi approval ke petugas
10. **Petugas:** Clock out (presensi)

### Workflow 2: Weekly Performance Review

1. **Supervisor:** View dashboard performance chart
2. **Supervisor:** Check individual petugas reports
3. **Supervisor:** Create penilaian (evaluation)
4. **System:** Send evaluation notification to petugas
5. **System:** Send weekly summary to all petugas

---

## Development Server

### Start Server

```bash
php artisan serve
```

Akses: [http://localhost:8000/admin](http://localhost:8000/admin)

### Storage Link

Jika gambar tidak muncul, pastikan storage link sudah dibuat:

```bash
php artisan storage:link
```

### Clear Cache (jika perlu)

```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

---

## Testing Features

### Test QR Code System

1. Generate QR codes: Lokasi → Print QR Codes
2. Screenshot salah satu QR code
3. Buka QR Scanner di mobile
4. Scan screenshot QR code
5. Verify data lokasi muncul
6. Klik "Buat Laporan"
7. Verify lokasi ter-isi otomatis

### Test WhatsApp Notifications

```php
// Gunakan Tinker untuk test
php artisan tinker

use App\Services\FontteService;
use App\Services\NotificationTemplateService;

$fonnte = new FontteService();
$templates = new NotificationTemplateService();

// Test simple message
$fonnte->sendMessage('081234567890', 'Test message from E-Cleaning!');

// Test template
$jadwal = \App\Models\JadwalKebersihan::first();
$message = $templates->scheduleAssigned($jadwal);
$fonnte->sendMessage($jadwal->petugas->phone, $message);
```

### Test Image Compression

1. Upload foto di Activity Report (min 2MB)
2. Check folder `storage/app/public/images/`
3. Verify file berformat WebP
4. Compare ukuran original vs compressed

---

## Next Development Steps

### Phase 8: GPS Integration (Next)

- Browser Geolocation API for attendance
- GPS coordinate capture
- Location-based QR scan verification
- Distance validation

### Phase 9: Export Features

- PDF export untuk laporan
- Excel export untuk rekap bulanan
- Print friendly views

### Phase 10: Testing & Deployment

- Unit tests
- Feature tests
- Production deployment
- PostgreSQL migration
- Redis setup

---

## Troubleshooting

### QR Scanner tidak bisa akses kamera

**Solusi:**
- Pastikan menggunakan HTTPS (production)
- Berikan izin camera di browser settings
- Gunakan browser modern (Chrome/Safari/Firefox)

### Gambar tidak muncul

**Solusi:**
```bash
php artisan storage:link
chmod -R 755 storage
chmod -R 755 bootstrap/cache
```

### Fonnte notifications tidak terkirim

**Solusi:**
- Check FONNTE_TOKEN di .env
- Verify token aktif di dashboard Fonnte
- Check phone number format (harus valid Indonesia)
- Check notification logs di database

### Performance issues

**Solusi:**
- Enable cache: `php artisan config:cache`
- Enable route cache: `php artisan route:cache`
- Consider Redis untuk production

---

## Resources & Documentation

- **Main Documentation:** [README.md](README.md)
- **Design Specification:** [design.md](design.md)
- **Phase 6 & 7 Details:** [PHASE_6_7_SUMMARY.md](PHASE_6_7_SUMMARY.md)
- **Laravel Docs:** [https://laravel.com/docs](https://laravel.com/docs/12.x)
- **Filament Docs:** [https://filamentphp.com/docs](https://filamentphp.com/docs/4.x)
- **Fonnte Docs:** [https://docs.fonnte.com](https://docs.fonnte.com)

---

## Support & Contact

Untuk pertanyaan atau masalah, silakan hubungi development team.

---

**Last Updated:** October 21, 2025
**Current Version:** Phase 7 Complete
**Next Milestone:** Phase 8 - GPS Integration
