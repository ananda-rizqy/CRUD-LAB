<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AlatController;
use App\Http\Controllers\Api\DeviceController;
use Illuminate\Support\Facades\Route;

// / Menggunakan endpoint /auth/google agar lebih jelas bahwa ini login via SSO
Route::post('/auth/google', [AuthController::class, 'loginGoogle']);

// --- RUTE TERPROTEKSI (Harus Login via SSO/Sanctum) ---
Route::middleware('auth:sanctum')->group(function () {

    // Semua role (Mahasiswa, Dosen) bisa melihat data ketersediaan alat
    Route::get('/alat', [AlatController::class, 'index']);
    Route::get('/alat/{id}', [AlatController::class, 'show']);

    // Khusus Staff: Bisa melakukan Tambah, Edit, dan Hapus
    Route::middleware('role:staff')->group(function () {
        Route::post('/alat', [AlatController::class, 'store']);
        Route::put('/alat/{id}', [AlatController::class, 'update']);
        Route::delete('/alat/{id}', [AlatController::class, 'destroy']);
    });

    // 2. FITUR DEVICE
    Route::apiResource("device", DeviceController::class);

    // 3. FITUR  (Peminjaman & Ruang)
    // Route::get('peminjaman', [PeminjamanController::class, 'index']);
});