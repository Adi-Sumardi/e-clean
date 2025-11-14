<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\LokasiController;
use App\Http\Controllers\Api\JadwalKebersihanController;
use App\Http\Controllers\Api\ActivityReportController;
use App\Http\Controllers\Api\PenilaianController;
use App\Http\Controllers\Api\DashboardController;
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
    });

    // Lokasi routes
    Route::prefix('lokasi')->group(function () {
        Route::get('/', [LokasiController::class, 'index']);
        Route::get('/{id}', [LokasiController::class, 'show']);
    });

    // Jadwal Kebersihan routes
    Route::prefix('jadwal')->group(function () {
        Route::get('/', [JadwalKebersihanController::class, 'index']);
        Route::get('/today', [JadwalKebersihanController::class, 'today']);
        Route::get('/upcoming', [JadwalKebersihanController::class, 'upcoming']);
        Route::get('/{id}', [JadwalKebersihanController::class, 'show']);
    });

    // Activity Report routes
    Route::prefix('activity-reports')->group(function () {
        Route::get('/', [ActivityReportController::class, 'index']);
        Route::post('/', [ActivityReportController::class, 'store'])->middleware('throttle:30,1'); // Stricter limit for submissions
        Route::get('/statistics', [ActivityReportController::class, 'statistics']);
        Route::post('/bulk-submit', [ActivityReportController::class, 'bulkSubmit'])->middleware('throttle:10,1'); // Strict limit for bulk ops
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

});
