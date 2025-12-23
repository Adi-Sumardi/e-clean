<?php

use App\Http\Controllers\CameraPhotoController;
use App\Http\Controllers\GoogleAuthController;
use App\Http\Controllers\GuestComplaintController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/admin/login');
});

// Guest Complaint Routes (Public - for QR Code scanning)
Route::prefix('keluhan')->group(function () {
    Route::get('/{lokasi}', [GuestComplaintController::class, 'showForm'])->name('guest-complaint.form');
    Route::post('/', [GuestComplaintController::class, 'store'])->name('guest-complaint.store');
    Route::get('/{lokasi}/success', [GuestComplaintController::class, 'success'])->name('guest-complaint.success');
});

// Google OAuth Routes
Route::prefix('auth')->group(function () {
    Route::get('/google', [GoogleAuthController::class, 'redirectToGoogle'])->name('auth.google');
    Route::get('/google/callback', [GoogleAuthController::class, 'handleGoogleCallback'])->name('auth.google.callback');

    // Protected routes
    Route::middleware('auth')->group(function () {
        Route::post('/google/unlink', [GoogleAuthController::class, 'unlinkGoogle'])->name('auth.google.unlink');
        Route::get('/login-methods', [GoogleAuthController::class, 'getLoginMethods'])->name('auth.login-methods');
    });
});

// Camera Photo API Routes
Route::middleware('auth')->prefix('api/camera')->group(function () {
    Route::post('/capture', [CameraPhotoController::class, 'capture'])->name('camera.capture');
    Route::get('/lokasi/{id}', [CameraPhotoController::class, 'getLokasiInfo'])->name('camera.lokasi');
});
