# 📁 E-Clean Project Structure

## Overview

Project ini telah direorganisasi menjadi 2 folder utama untuk memisahkan Backend (Laravel) dan Frontend (Flutter).

```
e-clean/
├── .claude/                  # Claude Code configuration
├── Backend/                  # Laravel 11 - REST API & Admin Panel
├── Frontend/                 # Flutter - Mobile Application
├── .gitignore               # Root gitignore
├── README.md                # Main documentation
├── QUICK_START.md          # Quick setup guide
└── PROJECT_STRUCTURE.md    # This file
```

---

## 🔧 Backend (Laravel API)

### Directory Structure

```
Backend/
├── app/
│   ├── Console/
│   │   └── Commands/              # Artisan commands (reminders, etc.)
│   ├── Exports/                   # Excel export classes
│   ├── Filament/                  # Admin panel
│   │   ├── Pages/                # Custom pages (Leaderboard, QR Scanner)
│   │   ├── Resources/            # Resource management
│   │   └── Widgets/              # Dashboard widgets
│   ├── Http/
│   │   ├── Controllers/
│   │   │   └── Api/             # API Controllers (39 endpoints)
│   │   │       ├── AuthController.php
│   │   │       ├── DashboardController.php
│   │   │       ├── LokasiController.php
│   │   │       ├── JadwalKebersihanController.php
│   │   │       ├── ActivityReportController.php
│   │   │       ├── PresensiController.php
│   │   │       └── PenilaianController.php
│   │   └── Resources/           # API Response Transformers
│   │       ├── UserResource.php
│   │       ├── LokasiResource.php
│   │       ├── JadwalKebersihanResource.php
│   │       ├── ActivityReportResource.php
│   │       ├── PresensiResource.php
│   │       └── PenilaianResource.php
│   ├── Models/                   # Eloquent Models
│   │   ├── User.php
│   │   ├── Lokasi.php
│   │   ├── JadwalKebersihan.php
│   │   ├── ActivityReport.php
│   │   ├── Presensi.php
│   │   ├── Penilaian.php
│   │   ├── NotificationLog.php
│   │   └── Setting.php
│   ├── Notifications/            # Laravel Notifications
│   ├── Observers/                # Model Observers
│   ├── Policies/                 # Authorization Policies
│   ├── Providers/                # Service Providers
│   ├── Services/                 # Business Logic Services
│   │   ├── FontteService.php   # WhatsApp Integration
│   │   ├── GPSService.php      # GPS Processing
│   │   ├── ImageService.php    # Image Processing
│   │   ├── QRCodeService.php   # QR Code Generation
│   │   └── PDFExportService.php
│   └── Traits/
│       └── ApiResponse.php      # Standardized API responses
├── config/                       # Configuration files
├── database/
│   ├── migrations/              # Database migrations (14 files)
│   ├── seeders/                 # Database seeders
│   │   ├── RolePermissionSeeder.php
│   │   ├── AdminUserSeeder.php
│   │   └── DummyDataSeeder.php
│   └── database.sqlite          # SQLite database
├── public/                       # Public assets
│   ├── storage -> ../storage/app/public
│   ├── css/filament/            # Filament styles
│   └── js/filament/             # Filament scripts
├── resources/
│   ├── css/                     # Custom CSS
│   ├── views/                   # Blade templates
│   │   ├── filament/           # Filament custom views
│   │   └── pdf/                # PDF templates
│   └── js/                      # Custom JavaScript
├── routes/
│   ├── api.php                  # API Routes (v1)
│   ├── web.php                  # Web Routes
│   └── console.php              # Console Commands
├── storage/
│   ├── app/
│   │   └── public/             # Uploaded files
│   │       ├── activity-reports/
│   │       ├── presensi/
│   │       └── lokasi/
│   ├── framework/              # Framework cache
│   └── logs/                   # Application logs
├── tests/                       # PHPUnit tests
├── vendor/                      # Composer dependencies
├── .env                        # Environment configuration
├── .env.example               # Environment template
├── artisan                     # Laravel CLI
├── composer.json              # PHP dependencies
├── package.json               # NPM dependencies
├── phpunit.xml               # PHPUnit configuration
├── vite.config.js            # Vite configuration
└── API_DOCUMENTATION.md      # Complete API docs (1370 lines)
```

### Key Files & Purposes

| File/Folder | Purpose |
|-------------|---------|
| `app/Http/Controllers/Api/` | REST API Controllers |
| `app/Http/Resources/` | API Response Transformers |
| `app/Filament/` | Admin Panel Configuration |
| `app/Models/` | Database Models |
| `app/Services/` | Business Logic |
| `app/Traits/ApiResponse.php` | Standardized API responses |
| `database/migrations/` | Database schema |
| `database/seeders/` | Sample data |
| `routes/api.php` | API endpoint definitions |
| `API_DOCUMENTATION.md` | Complete API documentation |

---

## 📱 Frontend (Flutter App)

### Directory Structure

```
Frontend/
├── android/                     # Android native code
├── ios/                        # iOS native code
├── lib/
│   ├── main.dart              # App entry point
│   ├── config/                # App configuration
│   │   ├── api_config.dart   # API endpoints
│   │   ├── app_config.dart   # App settings
│   │   └── theme_config.dart # Theme configuration
│   ├── models/                # Data models
│   │   ├── user.dart
│   │   ├── lokasi.dart
│   │   ├── jadwal.dart
│   │   ├── activity_report.dart
│   │   ├── presensi.dart
│   │   └── penilaian.dart
│   ├── services/              # API & Business Logic
│   │   ├── api_service.dart  # HTTP client
│   │   ├── auth_service.dart # Authentication
│   │   ├── storage_service.dart
│   │   └── location_service.dart
│   ├── screens/               # UI Screens
│   │   ├── auth/
│   │   │   ├── login_screen.dart
│   │   │   └── register_screen.dart
│   │   ├── dashboard/
│   │   │   ├── dashboard_screen.dart
│   │   │   ├── admin_dashboard.dart
│   │   │   └── petugas_dashboard.dart
│   │   ├── attendance/
│   │   │   ├── check_in_screen.dart
│   │   │   ├── check_out_screen.dart
│   │   │   └── attendance_history.dart
│   │   ├── reports/
│   │   │   ├── create_report_screen.dart
│   │   │   ├── report_list_screen.dart
│   │   │   └── report_detail_screen.dart
│   │   ├── schedule/
│   │   │   └── schedule_screen.dart
│   │   ├── evaluation/
│   │   │   └── evaluation_screen.dart
│   │   ├── leaderboard/
│   │   │   └── leaderboard_screen.dart
│   │   └── profile/
│   │       └── profile_screen.dart
│   ├── widgets/               # Reusable Widgets
│   │   ├── common/
│   │   ├── cards/
│   │   └── forms/
│   └── utils/                 # Helper Functions
│       ├── constants.dart
│       ├── validators.dart
│       └── formatters.dart
├── assets/                    # Static Assets
│   ├── images/
│   ├── icons/
│   └── fonts/
├── test/                      # Unit & Widget Tests
├── pubspec.yaml              # Flutter dependencies
├── analysis_options.yaml     # Dart analyzer config
└── README.md                 # Flutter README
```

### Planned Dependencies (pubspec.yaml)

```yaml
dependencies:
  flutter:
    sdk: flutter

  # State Management
  provider: ^6.1.1
  # or: flutter_bloc, riverpod

  # HTTP & API
  dio: ^5.4.0
  http: ^1.1.2

  # Local Storage
  shared_preferences: ^2.2.2
  hive: ^2.2.3

  # Image
  image_picker: ^1.0.7
  cached_network_image: ^3.3.1

  # Location
  geolocator: ^10.1.0
  location: ^5.0.3

  # UI Components
  flutter_svg: ^2.0.9
  google_fonts: ^6.1.0

  # Utilities
  intl: ^0.19.0
  equatable: ^2.0.5

  # Notifications
  firebase_messaging: ^14.7.9
```

---

## 🔄 Data Flow

### API Request Flow

```
Flutter App
    ↓
HTTP Request (Dio/HTTP)
    ↓
Laravel API (routes/api.php)
    ↓
Controller (app/Http/Controllers/Api/)
    ↓
Service Layer (app/Services/)
    ↓
Model (app/Models/)
    ↓
Database (SQLite/MySQL)
    ↓
Resource Transformer (app/Http/Resources/)
    ↓
JSON Response (with ApiResponse trait)
    ↓
Flutter App (Parse & Display)
```

### Authentication Flow

```
1. User Login (Flutter)
   ↓
2. POST /api/v1/auth/login
   ↓
3. Validate Credentials (Laravel)
   ↓
4. Generate Sanctum Token
   ↓
5. Return Token + User Data
   ↓
6. Store Token (Flutter - SharedPreferences)
   ↓
7. Use Token in Header: "Authorization: Bearer {token}"
```

---

## 📊 Database Schema

### Main Tables

```sql
users                    # User accounts
├── id
├── name
├── email
├── password
├── phone
└── timestamps

roles                   # User roles
└── permissions        # User permissions

lokasis                # Cleaning locations
├── id
├── kode_lokasi
├── nama_lokasi
├── kategori
├── lantai
├── deskripsi
├── foto
├── latitude
├── longitude
└── is_active

jadwal_kebersihanans   # Cleaning schedules
├── id
├── petugas_id → users
├── lokasi_id → lokasis
├── tanggal
├── shift
├── jam_mulai
├── jam_selesai
├── status
└── catatan

activity_reports       # Activity reports
├── id
├── jadwal_id → jadwal_kebersihanans
├── lokasi_id → lokasis
├── petugas_id → users
├── tanggal
├── jam_mulai
├── jam_selesai
├── kegiatan
├── foto_sebelum (JSON)
├── foto_sesudah (JSON)
├── koordinat_lokasi
├── catatan_petugas
├── catatan_supervisor
├── status
├── rating
├── approved_at
├── approver_id → users
└── rejected_reason

presensis             # Attendance records
├── id
├── petugas_id → users
├── tanggal
├── jam_masuk
├── jam_keluar
├── foto_masuk
├── foto_keluar
├── lokasi_masuk
├── lokasi_keluar
├── keterangan
├── status
├── is_late
└── total_jam_kerja

penilaians           # Performance evaluations
├── id
├── petugas_id → users
├── penilai_id → users
├── periode_bulan
├── periode_tahun
├── skor_kehadiran
├── skor_kualitas
├── skor_ketepatan_waktu
├── skor_kebersihan
├── total_skor
├── rata_rata
├── kategori
└── catatan
```

---

## 🚀 Development Workflow

### Backend Development

1. **Start Server:**
   ```bash
   cd Backend
   php artisan serve
   ```

2. **Monitor Logs:**
   ```bash
   tail -f storage/logs/laravel.log
   ```

3. **Clear Caches:**
   ```bash
   php artisan optimize:clear
   ```

4. **Run Tests:**
   ```bash
   php artisan test
   ```

### Frontend Development

1. **Start App:**
   ```bash
   cd Frontend
   flutter run
   ```

2. **Hot Reload:** Press `r` in terminal

3. **Hot Restart:** Press `R` in terminal

4. **Run Tests:**
   ```bash
   flutter test
   ```

---

## 📦 Dependencies

### Backend (composer.json)
- Laravel Framework 11.x
- Filament 4.1
- Laravel Sanctum 4.2
- Spatie Laravel Permission 6.x
- Intervention Image
- Laravel Excel
- DomPDF
- SimpleSoftwareIO/simple-qrcode
- Guava Calendar

### Frontend (pubspec.yaml)
- Flutter SDK 3.32.4
- Dart 3.8.1
- (Dependencies to be added during development)

---

## 🔐 Security

### Backend
- ✅ Sanctum token authentication
- ✅ Role-based access control
- ✅ Permission-based authorization
- ✅ CORS configuration
- ✅ Rate limiting
- ✅ Input validation
- ✅ SQL injection protection
- ✅ XSS protection

### Frontend
- ✅ Secure token storage
- ✅ HTTPS only in production
- ✅ Input validation
- ✅ Secure image upload
- ✅ Biometric authentication (planned)

---

## 📝 Environment Configuration

### Backend (.env)
```env
APP_NAME="E-Clean API"
APP_URL=http://localhost:8000
DB_CONNECTION=sqlite
FILESYSTEM_DISK=public
SANCTUM_STATEFUL_DOMAINS=localhost,127.0.0.1
```

### Frontend (config/api_config.dart)
```dart
class ApiConfig {
  static const String baseUrl = 'http://localhost:8000/api/v1';
  static const String storageUrl = 'http://localhost:8000/storage';
}
```

---

## 🎯 Project Status

### ✅ Completed
- [x] Backend API (39 endpoints)
- [x] Admin Panel (Filament)
- [x] Database schema & migrations
- [x] Authentication system
- [x] Role-based access control
- [x] API documentation
- [x] Project reorganization
- [x] Flutter project structure

### 🚧 In Progress
- [ ] Flutter UI implementation
- [ ] API integration in Flutter
- [ ] State management setup
- [ ] Offline support

### 📅 Planned
- [ ] Push notifications
- [ ] Real-time updates
- [ ] Advanced analytics
- [ ] Multi-language support
- [ ] Dark mode
- [ ] Biometric authentication

---

**Last Updated:** October 23, 2025
