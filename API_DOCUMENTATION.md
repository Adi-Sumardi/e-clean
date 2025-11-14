# E-Clean API Documentation

**Base URL:** `http://your-domain.com/api/v1`

**Authentication:** All endpoints (except Register and Login) require Bearer token authentication.

**Headers for protected routes:**
```
Authorization: Bearer {your-access-token}
Content-Type: application/json
```

**For multipart/form-data requests:**
```
Authorization: Bearer {your-access-token}
Content-Type: multipart/form-data
```

---

## Table of Contents
1. [Authentication](#authentication)
2. [Lokasi (Locations)](#lokasi-locations)
3. [Jadwal Kebersihan (Cleaning Schedules)](#jadwal-kebersihan-cleaning-schedules)
4. [Activity Reports](#activity-reports)
5. [Presensi (Attendance)](#presensi-attendance)
6. [Penilaian (Performance Evaluation)](#penilaian-performance-evaluation)
7. [Dashboard](#dashboard)
8. [Error Responses](#error-responses)

---

## Authentication

### 1. Register
**Endpoint:** `POST /auth/register`

**Description:** Register a new user. Auto-assigns 'petugas' role.

**Request Body:**
```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "password123",
  "password_confirmation": "password123",
  "phone": "081234567890"
}
```

**Response (201 Created):**
```json
{
  "success": true,
  "message": "Registration successful",
  "data": {
    "user": {
      "id": 1,
      "name": "John Doe",
      "email": "john@example.com",
      "phone": "081234567890",
      "roles": ["petugas"],
      "permissions": [],
      "created_at": "2025-10-23T10:00:00.000000Z",
      "updated_at": "2025-10-23T10:00:00.000000Z"
    },
    "token": "1|abcd1234efgh5678...",
    "token_type": "Bearer"
  }
}
```

---

### 2. Login
**Endpoint:** `POST /auth/login`

**Description:** Login with email and password. Revokes all previous tokens.

**Request Body:**
```json
{
  "email": "john@example.com",
  "password": "password123"
}
```

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Login successful",
  "data": {
    "user": {
      "id": 1,
      "name": "John Doe",
      "email": "john@example.com",
      "phone": "081234567890",
      "roles": ["petugas"],
      "permissions": ["view_Lokasi", "view_JadwalKebersihan", ...],
      "created_at": "2025-10-23T10:00:00.000000Z",
      "updated_at": "2025-10-23T10:00:00.000000Z"
    },
    "token": "2|xyz9876abc5432...",
    "token_type": "Bearer"
  }
}
```

---

### 3. Get Current User
**Endpoint:** `GET /auth/me`

**Response (200 OK):**
```json
{
  "success": true,
  "message": "User data retrieved successfully",
  "data": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "phone": "081234567890",
    "roles": ["petugas"],
    "permissions": [...],
    "created_at": "2025-10-23T10:00:00.000000Z",
    "updated_at": "2025-10-23T10:00:00.000000Z"
  }
}
```

---

### 4. Logout
**Endpoint:** `POST /auth/logout`

**Description:** Revoke current access token.

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Logout successful",
  "data": null
}
```

---

### 5. Refresh Token
**Endpoint:** `POST /auth/refresh-token`

**Description:** Revoke current token and generate new one.

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Token refreshed successfully",
  "data": {
    "token": "3|newtoken123...",
    "token_type": "Bearer"
  }
}
```

---

### 6. Update Profile
**Endpoint:** `PUT /auth/profile`

**Request Body:**
```json
{
  "name": "John Updated",
  "phone": "081234567899",
  "current_password": "password123",
  "password": "newpassword123",
  "password_confirmation": "newpassword123"
}
```

**Notes:**
- All fields are optional
- `current_password` is required if changing password
- If not changing password, omit password fields

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Profile updated successfully",
  "data": {
    "id": 1,
    "name": "John Updated",
    "email": "john@example.com",
    "phone": "081234567899",
    "roles": ["petugas"],
    "permissions": [...],
    "created_at": "2025-10-23T10:00:00.000000Z",
    "updated_at": "2025-10-23T10:30:00.000000Z"
  }
}
```

---

## Lokasi (Locations)

### 1. Get All Locations
**Endpoint:** `GET /lokasi`

**Query Parameters:**
- `kategori` - Filter by category (ruang_kelas, toilet, koridor, kantin, etc.)
- `lantai` - Filter by floor number
- `search` - Search by name or code
- `is_active` - Filter by active status (true/false)
- `per_page` - Items per page (default: 15, use 'all' for no pagination)

**Example:** `GET /lokasi?kategori=toilet&lantai=1&per_page=20`

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Locations retrieved successfully",
  "data": {
    "data": [
      {
        "id": 1,
        "kode_lokasi": "RK-101",
        "nama_lokasi": "Ruang Kelas 101",
        "kategori": "ruang_kelas",
        "lantai": 1,
        "deskripsi": "Ruang kelas untuk kelas 10A",
        "foto": "http://localhost:8000/storage/lokasi/photo.jpg",
        "is_active": true,
        "created_at": "2025-10-23T10:00:00.000000Z",
        "updated_at": "2025-10-23T10:00:00.000000Z"
      }
    ],
    "pagination": {
      "current_page": 1,
      "last_page": 5,
      "per_page": 15,
      "total": 75,
      "from": 1,
      "to": 15
    }
  }
}
```

---

### 2. Get Single Location
**Endpoint:** `GET /lokasi/{id}`

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Location retrieved successfully",
  "data": {
    "id": 1,
    "kode_lokasi": "RK-101",
    "nama_lokasi": "Ruang Kelas 101",
    "kategori": "ruang_kelas",
    "lantai": 1,
    "deskripsi": "Ruang kelas untuk kelas 10A",
    "foto": "http://localhost:8000/storage/lokasi/photo.jpg",
    "is_active": true,
    "created_at": "2025-10-23T10:00:00.000000Z",
    "updated_at": "2025-10-23T10:00:00.000000Z"
  }
}
```

---

## Jadwal Kebersihan (Cleaning Schedules)

### 1. Get All Schedules
**Endpoint:** `GET /jadwal`

**Query Parameters:**
- `start_date` & `end_date` - Date range filter (YYYY-MM-DD)
- `date` - Specific date filter
- `shift` - Filter by shift (pagi, siang, sore)
- `status` - Filter by status (scheduled, in_progress, completed, cancelled)
- `lokasi_id` - Filter by location
- `petugas_id` - Filter by petugas (admin/supervisor only)
- `per_page` - Items per page (default: 15)

**Example:** `GET /jadwal?shift=pagi&status=scheduled&per_page=20`

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Schedules retrieved successfully",
  "data": {
    "data": [
      {
        "id": 1,
        "tanggal": "2025-10-23",
        "shift": "pagi",
        "jam_mulai": "07:00:00",
        "jam_selesai": "10:00:00",
        "status": "scheduled",
        "catatan": null,
        "petugas": {
          "id": 1,
          "name": "John Doe",
          "phone": "081234567890"
        },
        "lokasi": {
          "id": 1,
          "kode_lokasi": "RK-101",
          "nama_lokasi": "Ruang Kelas 101",
          "kategori": "ruang_kelas",
          "lantai": 1,
          "deskripsi": null,
          "foto": null,
          "is_active": true
        },
        "created_at": "2025-10-23T10:00:00.000000Z",
        "updated_at": "2025-10-23T10:00:00.000000Z"
      }
    ],
    "pagination": {...}
  }
}
```

---

### 2. Get Single Schedule
**Endpoint:** `GET /jadwal/{id}`

**Note:** Petugas can only view their own schedules.

**Response (200 OK):** Same format as single schedule item above.

---

### 3. Get Today's Schedules
**Endpoint:** `GET /jadwal/today`

**Description:** Get all schedules for today. Petugas sees only their own.

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Today's schedules retrieved successfully",
  "data": [...]
}
```

---

### 4. Get Upcoming Schedules
**Endpoint:** `GET /jadwal/upcoming`

**Description:** Get schedules for the next 7 days. Petugas sees only their own.

**Query Parameters:**
- `days` - Number of days to fetch (default: 7, max: 30)

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Upcoming schedules retrieved successfully",
  "data": [...]
}
```

---

## Activity Reports

### 1. Get All Activity Reports
**Endpoint:** `GET /activity-reports`

**Query Parameters:**
- `start_date` & `end_date` - Date range filter
- `date` - Specific date filter
- `month` & `year` - Filter by month and year
- `status` - Filter by status (draft, submitted, approved, rejected)
- `lokasi_id` - Filter by location
- `petugas_id` - Filter by petugas (admin/supervisor only)
- `jadwal_id` - Filter by schedule
- `min_rating` - Minimum rating filter
- `search` - Search in activity description and notes
- `per_page` - Items per page (default: 15)

**Example:** `GET /activity-reports?status=approved&min_rating=4&per_page=20`

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Activity reports retrieved successfully",
  "data": {
    "data": [
      {
        "id": 1,
        "tanggal": "2025-10-23",
        "jam_mulai": "07:00:00",
        "jam_selesai": "10:00:00",
        "kegiatan": "Membersihkan ruang kelas 101",
        "foto_sebelum": [
          "http://localhost:8000/storage/activity-reports/before/photo1.jpg"
        ],
        "foto_sesudah": [
          "http://localhost:8000/storage/activity-reports/after/photo2.jpg"
        ],
        "koordinat_lokasi": "-6.200000,106.816666",
        "catatan_petugas": "Pembersihan berjalan lancar",
        "catatan_supervisor": "Sangat baik",
        "status": "approved",
        "rating": 5,
        "approved_at": "2025-10-23T15:00:00.000000Z",
        "rejected_reason": null,
        "petugas": {
          "id": 1,
          "name": "John Doe",
          "email": "john@example.com",
          "phone": "081234567890"
        },
        "lokasi": {...},
        "jadwal": {...},
        "approver": {
          "id": 2,
          "name": "Supervisor Name",
          "email": "supervisor@example.com"
        },
        "created_at": "2025-10-23T10:00:00.000000Z",
        "updated_at": "2025-10-23T15:00:00.000000Z"
      }
    ],
    "pagination": {...}
  }
}
```

---

### 2. Create Activity Report
**Endpoint:** `POST /activity-reports`

**Content-Type:** `multipart/form-data`

**Form Data:**
- `jadwal_id` (required) - Schedule ID
- `lokasi_id` (required) - Location ID
- `tanggal` (required) - Date (YYYY-MM-DD)
- `jam_mulai` (required) - Start time (HH:mm)
- `jam_selesai` (required) - End time (HH:mm)
- `kegiatan` (required) - Activity description (max 1000 chars)
- `foto_sebelum[]` (optional) - Before photos (array of images, max 5MB each)
- `foto_sesudah[]` (optional) - After photos (array of images, max 5MB each)
- `koordinat_lokasi` (optional) - GPS coordinates
- `catatan_petugas` (optional) - Petugas notes
- `status` (optional) - draft or submitted (default: draft)

**Example using Postman:**
1. Select POST method
2. Choose Body > form-data
3. Add fields as shown above
4. For photos, select "File" type and choose images

**Response (201 Created):**
```json
{
  "success": true,
  "message": "Activity report created successfully",
  "data": {...}
}
```

---

### 3. Get Single Activity Report
**Endpoint:** `GET /activity-reports/{id}`

**Note:** Petugas can only view their own reports.

**Response (200 OK):** Same format as single report item above.

---

### 4. Update Activity Report
**Endpoint:** `POST /activity-reports/{id}`

**Content-Type:** `multipart/form-data`

**Note:** Using POST instead of PUT/PATCH for multipart support. Petugas can only update draft/submitted reports.

**Form Data:** Same as Create, all fields optional

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Activity report updated successfully",
  "data": {...}
}
```

---

### 5. Delete Activity Report
**Endpoint:** `DELETE /activity-reports/{id}`

**Note:** Petugas can only delete draft reports. Admin/Supervisor can delete any.

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Activity report deleted successfully",
  "data": null
}
```

---

### 6. Get Activity Report Statistics
**Endpoint:** `GET /activity-reports/statistics`

**Query Parameters:**
- `start_date` & `end_date` - Date range filter
- `month` & `year` - Filter by month and year
- `petugas_id` - Filter by petugas (admin/supervisor only)

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Statistics retrieved successfully",
  "data": {
    "total_reports": 50,
    "draft_reports": 5,
    "submitted_reports": 10,
    "approved_reports": 30,
    "rejected_reports": 5,
    "average_rating": 4.2
  }
}
```

---

### 7. Bulk Submit Reports
**Endpoint:** `POST /activity-reports/bulk-submit`

**Description:** Submit multiple draft reports at once (Petugas only).

**Request Body:**
```json
{
  "report_ids": [1, 2, 3, 4, 5]
}
```

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Successfully submitted 5 reports",
  "data": {
    "updated_count": 5
  }
}
```

---

## Presensi (Attendance)

### 1. Get All Attendance Records
**Endpoint:** `GET /presensi`

**Query Parameters:**
- `start_date` & `end_date` - Date range filter
- `date` - Specific date filter
- `month` & `year` - Filter by month and year
- `status` - Filter by status (hadir, izin, sakit, alpha)
- `petugas_id` - Filter by petugas (admin/supervisor only)
- `is_late` - Filter by late status (true/false)
- `per_page` - Items per page (default: 15)

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Attendance records retrieved successfully",
  "data": {
    "data": [
      {
        "id": 1,
        "tanggal": "2025-10-23",
        "jam_masuk": "07:30:00",
        "jam_keluar": "16:00:00",
        "foto_masuk": "http://localhost:8000/storage/presensi/masuk/photo.jpg",
        "foto_keluar": "http://localhost:8000/storage/presensi/keluar/photo.jpg",
        "lokasi_masuk": "-6.200000,106.816666",
        "lokasi_keluar": "-6.200000,106.816666",
        "keterangan": null,
        "status": "hadir",
        "is_late": false,
        "total_jam_kerja": 8.5,
        "petugas": {
          "id": 1,
          "name": "John Doe",
          "email": "john@example.com",
          "phone": "081234567890"
        },
        "created_at": "2025-10-23T07:30:00.000000Z",
        "updated_at": "2025-10-23T16:00:00.000000Z"
      }
    ],
    "pagination": {...}
  }
}
```

---

### 2. Check-In (Clock In)
**Endpoint:** `POST /presensi/check-in`

**Content-Type:** `multipart/form-data`

**Form Data:**
- `foto_masuk` (optional) - Photo when checking in (image, max 5MB)
- `lokasi_masuk` (optional) - GPS coordinates
- `keterangan` (optional) - Notes

**Response (201 Created):**
```json
{
  "success": true,
  "message": "Check-in successful",
  "data": {
    "id": 1,
    "tanggal": "2025-10-23",
    "jam_masuk": "07:30:00",
    "jam_keluar": null,
    "foto_masuk": "http://localhost:8000/storage/presensi/masuk/photo.jpg",
    "foto_keluar": null,
    "lokasi_masuk": "-6.200000,106.816666",
    "lokasi_keluar": null,
    "keterangan": null,
    "status": "hadir",
    "is_late": false,
    "total_jam_kerja": null,
    "petugas": {...}
  }
}
```

---

### 3. Check-Out (Clock Out)
**Endpoint:** `POST /presensi/check-out`

**Content-Type:** `multipart/form-data`

**Form Data:**
- `foto_keluar` (optional) - Photo when checking out (image, max 5MB)
- `lokasi_keluar` (optional) - GPS coordinates
- `keterangan` (optional) - Notes

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Check-out successful",
  "data": {
    "id": 1,
    "tanggal": "2025-10-23",
    "jam_masuk": "07:30:00",
    "jam_keluar": "16:00:00",
    "total_jam_kerja": 8.5,
    ...
  }
}
```

---

### 4. Get Today's Attendance Status
**Endpoint:** `GET /presensi/today-status`

**Description:** Check if user has checked in/out today.

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Today's attendance status retrieved successfully",
  "data": {
    "has_checked_in": true,
    "has_checked_out": false,
    "presensi": {...}
  }
}
```

---

### 5. Get Attendance Statistics
**Endpoint:** `GET /presensi/statistics`

**Query Parameters:**
- `start_date` & `end_date` - Date range filter
- `month` & `year` - Filter by month and year
- `petugas_id` - Filter by petugas (admin/supervisor only)

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Statistics retrieved successfully",
  "data": {
    "total_days": 20,
    "hadir_count": 18,
    "izin_count": 1,
    "sakit_count": 1,
    "alpha_count": 0,
    "late_count": 3,
    "average_work_hours": 8.2
  }
}
```

---

### 6. Create Manual Attendance (Admin Only)
**Endpoint:** `POST /presensi`

**Content-Type:** `multipart/form-data`

**Note:** Only admin/supervisor can create manual attendance records.

**Form Data:**
- `petugas_id` (required)
- `tanggal` (required) - Date (YYYY-MM-DD)
- `jam_masuk` (optional) - Time (HH:mm)
- `jam_keluar` (optional) - Time (HH:mm)
- `foto_masuk` (optional) - Image
- `foto_keluar` (optional) - Image
- `lokasi_masuk` (optional)
- `lokasi_keluar` (optional)
- `keterangan` (optional)
- `status` (required) - hadir, izin, sakit, or alpha
- `is_late` (optional) - boolean

**Response (201 Created):**
```json
{
  "success": true,
  "message": "Attendance record created successfully",
  "data": {...}
}
```

---

### 7. Update Attendance (Admin Only)
**Endpoint:** `POST /presensi/{id}`

**Content-Type:** `multipart/form-data`

**Note:** Only admin/supervisor can update attendance records.

**Form Data:** Same as Create, all fields optional

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Attendance record updated successfully",
  "data": {...}
}
```

---

### 8. Delete Attendance (Admin Only)
**Endpoint:** `DELETE /presensi/{id}`

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Attendance record deleted successfully",
  "data": null
}
```

---

## Penilaian (Performance Evaluation)

### 1. Get All Evaluations
**Endpoint:** `GET /penilaian`

**Query Parameters:**
- `petugas_id` - Filter by petugas (admin/supervisor only)
- `periode_bulan` - Filter by month (1-12)
- `periode_tahun` - Filter by year
- `kategori` - Filter by category (Sangat Baik, Baik, Cukup, Kurang)
- `min_rata_rata` - Minimum average score
- `per_page` - Items per page (default: 15)

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Evaluations retrieved successfully",
  "data": {
    "data": [
      {
        "id": 1,
        "periode_bulan": 10,
        "periode_tahun": 2025,
        "skor_kehadiran": 90,
        "skor_kualitas": 85,
        "skor_ketepatan_waktu": 88,
        "skor_kebersihan": 92,
        "total_skor": 355,
        "rata_rata": 88.75,
        "kategori": "Sangat Baik",
        "catatan": "Performa sangat baik bulan ini",
        "petugas": {
          "id": 1,
          "name": "John Doe",
          "email": "john@example.com",
          "phone": "081234567890"
        },
        "penilai": {
          "id": 2,
          "name": "Supervisor Name",
          "email": "supervisor@example.com"
        },
        "created_at": "2025-10-23T10:00:00.000000Z",
        "updated_at": "2025-10-23T10:00:00.000000Z"
      }
    ],
    "pagination": {...}
  }
}
```

---

### 2. Create Evaluation (Admin/Supervisor Only)
**Endpoint:** `POST /penilaian`

**Request Body:**
```json
{
  "petugas_id": 1,
  "periode_bulan": 10,
  "periode_tahun": 2025,
  "skor_kehadiran": 90,
  "skor_kualitas": 85,
  "skor_ketepatan_waktu": 88,
  "skor_kebersihan": 92,
  "catatan": "Performa sangat baik bulan ini"
}
```

**Note:** Score range: 0-100. Category auto-calculated:
- ≥85: Sangat Baik
- ≥70: Baik
- ≥60: Cukup
- <60: Kurang

**Response (201 Created):**
```json
{
  "success": true,
  "message": "Evaluation created successfully",
  "data": {...}
}
```

---

### 3. Get Single Evaluation
**Endpoint:** `GET /penilaian/{id}`

**Note:** Petugas can only view their own evaluations.

**Response (200 OK):** Same format as single evaluation item above.

---

### 4. Update Evaluation (Admin/Supervisor Only)
**Endpoint:** `PUT /penilaian/{id}`

**Request Body:** Same as Create, all fields optional

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Evaluation updated successfully",
  "data": {...}
}
```

---

### 5. Delete Evaluation (Admin/Supervisor Only)
**Endpoint:** `DELETE /penilaian/{id}`

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Evaluation deleted successfully",
  "data": null
}
```

---

### 6. Get Evaluation Statistics
**Endpoint:** `GET /penilaian/statistics`

**Query Parameters:**
- `petugas_id` - Filter by petugas (admin/supervisor only)
- `start_month` & `start_year` - Start period
- `end_month` & `end_year` - End period

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Statistics retrieved successfully",
  "data": {
    "total_evaluations": 12,
    "by_category": {
      "sangat_baik": 5,
      "baik": 4,
      "cukup": 2,
      "kurang": 1
    },
    "averages": {
      "kehadiran": 85.5,
      "kualitas": 82.3,
      "ketepatan_waktu": 84.7,
      "kebersihan": 86.2,
      "total": 84.7
    }
  }
}
```

---

### 7. Get Latest Evaluation
**Endpoint:** `GET /penilaian/latest`

**Query Parameters:**
- `petugas_id` - Required for admin/supervisor, auto-set for petugas

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Latest evaluation retrieved successfully",
  "data": {...}
}
```

---

### 8. Get Evaluation History
**Endpoint:** `GET /penilaian/history`

**Description:** Get evaluation trend over time for charts.

**Query Parameters:**
- `petugas_id` - Required for admin/supervisor
- `limit` - Number of records (default: 12)

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Evaluation history retrieved successfully",
  "data": [
    {
      "period": "2025-01",
      "periode_bulan": 1,
      "periode_tahun": 2025,
      "skor_kehadiran": 85,
      "skor_kualitas": 80,
      "skor_ketepatan_waktu": 82,
      "skor_kebersihan": 88,
      "rata_rata": 83.75,
      "kategori": "Baik"
    },
    ...
  ]
}
```

---

## Dashboard

### 1. Get Dashboard Data
**Endpoint:** `GET /dashboard`

**Description:** Returns role-specific dashboard data.

**For Petugas:**
```json
{
  "success": true,
  "message": "Dashboard data retrieved successfully",
  "data": {
    "user_info": {
      "name": "John Doe",
      "role": "petugas"
    },
    "today": {
      "date": "2025-10-23",
      "schedule_count": 3,
      "schedules": [...],
      "attendance": {
        "has_checked_in": true,
        "has_checked_out": false,
        "jam_masuk": "07:30:00",
        "jam_keluar": null,
        "is_late": false
      }
    },
    "monthly_stats": {
      "month": 10,
      "year": 2025,
      "attendance": {
        "total_days": 20,
        "present": 18,
        "late": 2,
        "permission": 1,
        "sick": 1,
        "absent": 0
      },
      "reports": {
        "total": 45,
        "draft": 3,
        "submitted": 5,
        "approved": 35,
        "rejected": 2,
        "average_rating": 4.5
      }
    },
    "pending_tasks": {
      "pending_reports": 8
    },
    "latest_evaluation": {
      "period": "2025-10",
      "rata_rata": 88.75,
      "kategori": "Sangat Baik",
      "scores": {
        "kehadiran": 90,
        "kualitas": 85,
        "ketepatan_waktu": 88,
        "kebersihan": 92
      }
    }
  }
}
```

**For Admin/Supervisor:**
```json
{
  "success": true,
  "message": "Dashboard data retrieved successfully",
  "data": {
    "user_info": {
      "name": "Admin Name",
      "role": "admin"
    },
    "overview": {
      "total_petugas": 25,
      "total_lokasi": 50,
      "pending_approvals": 12
    },
    "today": {
      "date": "2025-10-23",
      "total_schedules": 30,
      "total_attendance": 23,
      "present": 23,
      "late": 3,
      "attendance_rate": 92.0
    },
    "monthly_stats": {
      "month": 10,
      "year": 2025,
      "reports": {
        "total": 500,
        "draft": 30,
        "submitted": 50,
        "approved": 400,
        "rejected": 20,
        "average_rating": 4.3
      },
      "attendance_rate": 94.5,
      "coverage_rate": 96.0,
      "cleaned_locations": 48
    },
    "top_performers": [
      {
        "petugas_id": 5,
        "name": "Best Petugas",
        "average_rating": 4.9,
        "total_reports": 25
      },
      ...
    ],
    "recent_reports": [...]
  }
}
```

---

### 2. Get Statistics for Charts
**Endpoint:** `GET /dashboard/statistics`

**Query Parameters:**
- `start_date` - Start date (default: 30 days ago)
- `end_date` - End date (default: today)
- `petugas_id` - Filter by petugas (admin/supervisor only)

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Statistics retrieved successfully",
  "data": {
    "period": {
      "start_date": "2025-09-23",
      "end_date": "2025-10-23"
    },
    "reports_trend": [
      {"date": "2025-10-01", "count": 5},
      {"date": "2025-10-02", "count": 8},
      ...
    ],
    "attendance_trend": [
      {"date": "2025-10-01", "count": 20, "present": 18, "late": 3},
      ...
    ],
    "reports_by_status": {
      "draft": 30,
      "submitted": 50,
      "approved": 400,
      "rejected": 20
    },
    "rating_trend": [
      {"date": "2025-10-01", "average_rating": 4.2},
      ...
    ]
  }
}
```

---

### 3. Get Leaderboard
**Endpoint:** `GET /dashboard/leaderboard`

**Description:** Ranking petugas by performance metrics.

**Query Parameters:**
- `month` - Month (default: current month)
- `year` - Year (default: current year)
- `limit` - Number of top performers (default: 10)

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Leaderboard retrieved successfully",
  "data": {
    "period": {
      "month": 10,
      "year": 2025
    },
    "leaderboard": [
      {
        "rank": 1,
        "petugas_id": 5,
        "name": "Top Performer",
        "total_reports": 30,
        "approved_reports": 28,
        "average_rating": 4.8,
        "attendance_rate": 100.0,
        "evaluation_score": 92.0,
        "overall_score": 94.4
      },
      ...
    ]
  }
}
```

**Overall Score Calculation:**
- Average Rating × 30%
- Attendance Rate × 30%
- Evaluation Score × 40%

---

## Error Responses

### Validation Error (422)
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "email": ["The email field is required."],
    "password": ["The password field must be at least 8 characters."]
  }
}
```

### Unauthorized (401)
```json
{
  "success": false,
  "message": "Unauthenticated."
}
```

### Forbidden (403)
```json
{
  "success": false,
  "message": "Forbidden"
}
```

### Not Found (404)
```json
{
  "success": false,
  "message": "Resource not found"
}
```

### Server Error (500)
```json
{
  "success": false,
  "message": "Internal server error"
}
```

---

## Testing with Postman

### 1. Setup Environment
Create a new environment with variables:
- `base_url`: `http://localhost:8000/api/v1`
- `token`: (will be set automatically after login)

### 2. Login and Get Token
1. **Request:** `POST {{base_url}}/auth/login`
2. **Body (JSON):**
   ```json
   {
     "email": "admin@example.com",
     "password": "password"
   }
   ```
3. **Tests tab - Add script to save token:**
   ```javascript
   var jsonData = pm.response.json();
   pm.environment.set("token", jsonData.data.token);
   ```

### 3. Use Token in Protected Routes
1. Go to Authorization tab
2. Type: Bearer Token
3. Token: `{{token}}`

Or add to Headers:
- Key: `Authorization`
- Value: `Bearer {{token}}`

### 4. Test File Upload
For endpoints with multipart/form-data:
1. **Body tab:** Select `form-data`
2. **Add fields:**
   - Text fields: Just type the value
   - File fields: Click dropdown next to KEY, select "File", then choose file
3. **Example for Activity Report:**
   - `jadwal_id`: 1
   - `lokasi_id`: 1
   - `tanggal`: 2025-10-23
   - `jam_mulai`: 07:00
   - `jam_selesai`: 10:00
   - `kegiatan`: Test activity
   - `foto_sebelum[]`: [Select File]
   - `foto_sesudah[]`: [Select File]

### 5. Test Pagination
- `GET {{base_url}}/activity-reports?per_page=10&page=2`
- `GET {{base_url}}/activity-reports?per_page=all` (no pagination)

### 6. Test Filtering
- `GET {{base_url}}/jadwal?shift=pagi&status=scheduled`
- `GET {{base_url}}/activity-reports?status=approved&min_rating=4`
- `GET {{base_url}}/presensi?month=10&year=2025&status=hadir`

---

## Rate Limiting

The API implements rate limiting to prevent abuse:
- **Default:** 60 requests per minute per user
- If exceeded, you'll receive a `429 Too Many Requests` response

---

## Best Practices

1. **Always include Authorization header** for protected routes
2. **Handle pagination** - Don't request `per_page=all` for large datasets
3. **Use appropriate filters** to reduce response size
4. **Compress images** before upload (max 5MB per image)
5. **Check `has_checked_in`** before allowing check-out
6. **Validate dates** on client side before sending
7. **Store token securely** - Never log or expose in URLs
8. **Refresh token** when receiving 401 responses
9. **Handle errors gracefully** - Show user-friendly messages

---

## Support

For issues or questions:
1. Check error response for specific validation messages
2. Verify request format matches documentation
3. Ensure proper authentication headers
4. Check API logs for server errors

---

**Last Updated:** October 23, 2025
**API Version:** 1.0
**Laravel Version:** 11
**Sanctum Version:** 4.2
