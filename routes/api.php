<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\LokasiController;
use App\Http\Controllers\Api\JadwalKebersihanController;
use App\Http\Controllers\Api\ActivityReportController;
use App\Http\Controllers\Api\PenilaianController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\UnitController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\Field\SatpamJadwalController;
use App\Http\Controllers\Api\Field\SatpamLaporanController;
use App\Http\Controllers\Api\Field\ObJadwalController;
use App\Http\Controllers\Api\Field\ObLaporanController;
use App\Http\Controllers\Api\Field\TokoJadwalController;
use App\Http\Controllers\Api\Field\TokoLaporanController;
use App\Http\Controllers\Api\GuestComplaintController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Public routes (no authentication required)
Route::prefix('v1')->group(function () {

    // Authentication routes
    Route::prefix('auth')->group(function () {
        Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:5,1'); // 5 attempts per minute
        Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1'); // 5 attempts per minute
    });

});

// Protected routes (require authentication with rate limiting)
Route::prefix('v1')->middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {

    // Auth routes (authenticated)
    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/refresh-token', [AuthController::class, 'refreshToken']);
        Route::put('/profile', [AuthController::class, 'updateProfile']);
        Route::post('/push-token', [AuthController::class, 'registerPushToken']);
        Route::delete('/push-token', [AuthController::class, 'unregisterPushToken']);
    });

    // Lokasi routes (read for all; manage for admin/supervisor)
    Route::prefix('lokasi')->group(function () {
        Route::get('/', [LokasiController::class, 'index']);
        Route::post('/', [LokasiController::class, 'store']);
        Route::get('/{id}', [LokasiController::class, 'show']);
        Route::put('/{id}', [LokasiController::class, 'update']);
        Route::delete('/{id}', [LokasiController::class, 'destroy']);
    });

    // Users management + petugas dropdowns
    Route::prefix('users')->group(function () {
        Route::get('/', [UserController::class, 'index']);
        Route::get('/roles', [UserController::class, 'roles']);
        Route::post('/', [UserController::class, 'store']);
        Route::get('/{id}', [UserController::class, 'show']);
        Route::put('/{id}', [UserController::class, 'update']);
        Route::delete('/{id}', [UserController::class, 'destroy']);
    });

    // Jadwal Kebersihan routes (read for all; manage for admin/supervisor)
    Route::prefix('jadwal')->group(function () {
        Route::get('/', [JadwalKebersihanController::class, 'index']);
        Route::get('/today', [JadwalKebersihanController::class, 'today']);
        Route::get('/upcoming', [JadwalKebersihanController::class, 'upcoming']);
        Route::post('/', [JadwalKebersihanController::class, 'store']);
        Route::get('/{id}', [JadwalKebersihanController::class, 'show']);
        Route::put('/{id}', [JadwalKebersihanController::class, 'update']);
        Route::delete('/{id}', [JadwalKebersihanController::class, 'destroy']);
    });

    // Activity Report routes
    Route::prefix('activity-reports')->group(function () {
        Route::get('/', [ActivityReportController::class, 'index']);
        Route::post('/', [ActivityReportController::class, 'store'])->middleware('throttle:30,1'); // Stricter limit for submissions
        Route::get('/statistics', [ActivityReportController::class, 'statistics']);
        Route::post('/bulk-submit', [ActivityReportController::class, 'bulkSubmit'])->middleware('throttle:10,1'); // Strict limit for bulk ops
        // Supervisor/admin approval workflow
        Route::post('/{id}/approve', [ActivityReportController::class, 'approve']);
        Route::post('/{id}/reject', [ActivityReportController::class, 'reject']);
        Route::get('/{id}', [ActivityReportController::class, 'show']);
        Route::post('/{id}', [ActivityReportController::class, 'update'])->middleware('throttle:30,1'); // Using POST for multipart/form-data
        Route::delete('/{id}', [ActivityReportController::class, 'destroy'])->middleware('throttle:20,1');
    });

    // Penilaian (Performance Evaluation) routes
    Route::prefix('penilaian')->group(function () {
        Route::get('/', [PenilaianController::class, 'index']);
        Route::post('/', [PenilaianController::class, 'store']);
        Route::get('/statistics', [PenilaianController::class, 'statistics']);
        Route::get('/latest', [PenilaianController::class, 'latest']);
        Route::get('/history', [PenilaianController::class, 'history']);
        Route::get('/{id}', [PenilaianController::class, 'show']);
        Route::put('/{id}', [PenilaianController::class, 'update']);
        Route::delete('/{id}', [PenilaianController::class, 'destroy']);
    });

    // Dashboard routes
    Route::prefix('dashboard')->group(function () {
        Route::get('/', [DashboardController::class, 'index']);
        Route::get('/statistics', [DashboardController::class, 'statistics']);
        Route::get('/leaderboard', [DashboardController::class, 'leaderboard']);
    });

    // Notifications feed (drives the bell icon on dashboards)
    Route::get('/notifications', [\App\Http\Controllers\Api\NotificationController::class, 'index']);

    // Guest Complaints management
    Route::prefix('guest-complaints')->group(function () {
        Route::get('/', [GuestComplaintController::class, 'index']);
        Route::post('/{id}/assign', [GuestComplaintController::class, 'assign']);
        Route::post('/{id}/status', [GuestComplaintController::class, 'updateStatus']);
    });

    // Units (for per-unit filtering + management)
    Route::prefix('units')->group(function () {
        Route::get('/', [UnitController::class, 'index']);
        Route::post('/', [UnitController::class, 'store']);
        Route::get('/{id}', [UnitController::class, 'show']);
        Route::put('/{id}', [UnitController::class, 'update']);
        Route::delete('/{id}', [UnitController::class, 'destroy']);
    });

    // ---- Satpam (security/patrol) domain ----
    Route::prefix('satpam')->group(function () {
        Route::prefix('jadwal')->group(function () {
            Route::get('/', [SatpamJadwalController::class, 'index']);
            Route::get('/today', [SatpamJadwalController::class, 'today']);
            Route::get('/upcoming', [SatpamJadwalController::class, 'upcoming']);
            Route::get('/{id}', [SatpamJadwalController::class, 'show']);
            Route::post('/', [SatpamJadwalController::class, 'store']);
            Route::delete('/{id}', [SatpamJadwalController::class, 'destroy']);
        });
        Route::prefix('laporan')->group(function () {
            Route::get('/', [SatpamLaporanController::class, 'index']);
            Route::post('/', [SatpamLaporanController::class, 'store'])->middleware('throttle:30,1');
            Route::get('/{id}', [SatpamLaporanController::class, 'show']);
            Route::post('/{id}/approve', [SatpamLaporanController::class, 'approve']);
            Route::post('/{id}/reject', [SatpamLaporanController::class, 'reject']);
        });
    });

    // ---- Office Boy (area service) domain ----
    Route::prefix('office-boy')->group(function () {
        Route::prefix('jadwal')->group(function () {
            Route::get('/', [ObJadwalController::class, 'index']);
            Route::get('/today', [ObJadwalController::class, 'today']);
            Route::get('/upcoming', [ObJadwalController::class, 'upcoming']);
            Route::get('/{id}', [ObJadwalController::class, 'show']);
            Route::post('/', [ObJadwalController::class, 'store']);
            Route::delete('/{id}', [ObJadwalController::class, 'destroy']);
        });
        Route::prefix('laporan')->group(function () {
            Route::get('/', [ObLaporanController::class, 'index']);
            Route::post('/', [ObLaporanController::class, 'store'])->middleware('throttle:30,1');
            Route::get('/{id}', [ObLaporanController::class, 'show']);
            Route::post('/{id}/approve', [ObLaporanController::class, 'approve']);
            Route::post('/{id}/reject', [ObLaporanController::class, 'reject']);
        });
    });

    // ---- Petugas Toko (store) domain ----
    Route::prefix('toko')->group(function () {
        Route::prefix('jadwal')->group(function () {
            Route::get('/', [TokoJadwalController::class, 'index']);
            Route::get('/today', [TokoJadwalController::class, 'today']);
            Route::get('/upcoming', [TokoJadwalController::class, 'upcoming']);
            Route::get('/{id}', [TokoJadwalController::class, 'show']);
            Route::post('/', [TokoJadwalController::class, 'store']);
            Route::delete('/{id}', [TokoJadwalController::class, 'destroy']);
        });
        Route::prefix('laporan')->group(function () {
            Route::get('/', [TokoLaporanController::class, 'index']);
            Route::post('/', [TokoLaporanController::class, 'store'])->middleware('throttle:30,1');
            Route::get('/{id}', [TokoLaporanController::class, 'show']);
            Route::post('/{id}/approve', [TokoLaporanController::class, 'approve']);
            Route::post('/{id}/reject', [TokoLaporanController::class, 'reject']);
        });
    });

});

// Note: Camera routes (/api/camera/*) are defined in web.php
// to properly handle web session authentication for Filament
