<?php

/*
|--------------------------------------------------------------------------
| Auth Routes — Google OAuth Only
|--------------------------------------------------------------------------
|
| Aplikasi ini hanya menggunakan Google OAuth untuk autentikasi.
| Route register, forgot-password, reset-password, verify-email,
| confirm-password, dan login email/password tidak digunakan.
|
*/

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\GoogleAuthController;
use Illuminate\Support\Facades\Route;

// Halaman login (menampilkan tombol "Masuk dengan Google")
Route::middleware('guest')->group(function () {
    Route::get('login', [AuthenticatedSessionController::class, 'create'])
        ->name('login');
});

// Google OAuth (accessible by both guests and authenticated users)
Route::get('auth/google', [GoogleAuthController::class, 'redirect'])
    ->name('auth.google');

Route::get('auth/google/callback', [GoogleAuthController::class, 'callback'])
    ->name('auth.google.callback');

Route::middleware('auth')->group(function () {
    // Reconnect Google (untuk re-grant Classroom scopes)
    Route::get('auth/google/reconnect', [GoogleAuthController::class, 'reconnect'])
        ->name('auth.google.reconnect');

    // Cek status akses Google Classroom (API)
    Route::get('auth/google/check-access', [GoogleAuthController::class, 'checkAccess'])
        ->name('auth.google.check-access');

    // Logout
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');
});
