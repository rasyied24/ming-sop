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

    // Semua role boleh membuka daftar SOP dan detail (isi yang tampil dibatasi Policy).
    Route::livewire('/sops', 'pages::sops.index')->name('sops.index');
    Route::livewire('/sops/{sop}', 'pages::sops.show')->name('sops.show');

    // PIC SOP dan Manager: direktori seluruh SOP. Tombol aksi di dalamnya mengikuti Policy.
    Route::livewire('/direktori-sop', 'pages::sops.manage')
        ->middleware('role:pic_sop,manager')
        ->name('sops.manage');

    // Hanya PIC SOP: membuat dan mengubah draf.
    Route::middleware('role:pic_sop')->group(function () {
        Route::livewire('/direktori-sop/buat', 'pages::sops.form')->name('sops.create');
        Route::livewire('/direktori-sop/{sop}/ubah', 'pages::sops.form')->name('sops.edit');
    });

    // Manager.
    Route::middleware('role:manager')->group(function () {
        Route::livewire('/dashboard', 'pages::dashboard')->name('dashboard');
        Route::livewire('/persetujuan', 'pages::approvals')->name('approvals.index');
    });

    // Auditor / HRD.
    Route::livewire('/kepatuhan', 'pages::compliance')
        ->middleware('role:auditor')
        ->name('compliance');
});