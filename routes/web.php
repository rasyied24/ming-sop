<?php

use App\Http\Controllers\Auth\LogoutController;
use Illuminate\Support\Facades\Route;

// "/" selalu diarahkan ke login. Kalau user sudah login, middleware guest
// otomatis meneruskannya ke halaman awal sesuai role (lihat bootstrap/app.php).
Route::redirect('/', '/login');

Route::middleware('guest')->group(function () {
    Route::livewire('/login', 'pages::auth.login')->name('login');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', LogoutController::class)->name('logout');

    // Semua role boleh membuka daftar SOP (isi yang tampil nanti dibatasi Policy).
    Route::livewire('/sops', 'pages::sops.index')->name('sops.index');

    Route::livewire('/kelola-sop', 'pages::sops.manage')
        ->middleware('role:pic_sop')
        ->name('sops.manage');

    Route::livewire('/dashboard', 'pages::dashboard')
        ->middleware('role:manager')
        ->name('dashboard');

    Route::livewire('/kepatuhan', 'pages::compliance')
        ->middleware('role:auditor')
        ->name('compliance');
});