<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\RandomizerController;
use App\Http\Controllers\Api\AlatController;
use App\Http\Controllers\Api\DeviceController;
use App\Http\Controllers\Api\PeminjamanController;
use App\Http\Controllers\Api\RiwayatpinjamController;
use App\Http\Controllers\Api\RiwayatruangController;
use App\Http\Controllers\Api\RuangController;
use App\Http\Controllers\Api\QrController;
use App\Models\PenggunaanRuang;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Random\Randomizer;

Route::post('/auth/sync', [AuthController::class, 'loginAndSyncSSO']);
Route::get('/generate-qr-pintu', [QrController::class, 'generatePintuMasuk']);

// --- RUTE TERPROTEKSI (Harus Login via SSO/Sanctum) ---
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/alat', [AlatController::class, 'index']);
    Route::get('/alat/{id}', [AlatController::class, 'show']);
    Route::get('/ruangan-list', [AlatController::class, 'getRuanganList']);
    Route::get('/jadwal', [RandomizerController::class, 'index']);
    Route::get('/mahasiswa', [RandomizerController::class, 'getMahasiswaKampus']);

    //FITUR DEVICE
    Route::apiResource("device", DeviceController::class);

    // Khusus role tendik: Bisa melakukan Tambah, Edit, dan Hapus
    Route::middleware('role:tendik')->group(function () {
        Route::post('/alat', [AlatController::class, 'store']);
        Route::put('/alat/{id}', [AlatController::class, 'update']);
        Route::delete('/alat/{id}', [AlatController::class, 'destroy']);
        Route::post('/peminjaman/{id}/setujui', [PeminjamanController::class, 'setujui']);
        Route::post('/peminjaman/{id}/tolak', [PeminjamanController::class, 'tolak']);
        Route::get('/peminjaman/semua', [PeminjamanController::class, 'index']); 
        Route::get('/peminjaman/monitor-riwayat', [PeminjamanController::class, 'index']);
        Route::get('/peminjaman/laporan-rusak', [PeminjamanController::class, 'laporanRusak']);
        Route::get('/tendik/riwayat-ruang', [RuangController::class, 'riwayatTendik']);
    });

    // Khusus role dosen:riwayat peminjaman dan penggunaan ruang
    Route::middleware('role:dosen')->group(function () {
        Route::get('/peminjaman/pantau-riwayat', [RiwayatpinjamController::class, 'riwayatDosen']);
        Route::get('/dosen/pantau-ruang', [RiwayatruangController::class, 'riwayatDosen']);
    });

        // Khusus role mahasiswa
        Route::middleware('role:mahasiswa')->group(function () {
        // daftar pinjaman aktif 
        Route::get('/peminjaman/aktif', function() {
            return App\Models\Peminjaman::with('alat')
                ->where('user_id', Auth::id())
                ->orderBy('created_at', 'desc')
                ->get();
        });
        //upload foto alat before
        Route::post('/peminjaman/{id}/upload-before', [App\Http\Controllers\Api\PeminjamanController::class, 'uploadBefore']);
        //Mengajukan, Upload Foto, dan Mengembalikan
        Route::post('/peminjaman/ajukan', [PeminjamanController::class, 'store']); // Identitas otomatis dari Auth
        Route::post('/peminjaman/{id}/upload-before', [PeminjamanController::class, 'uploadBefore']);
        Route::post('/peminjaman/{id}/kembalikan', [PeminjamanController::class, 'kembalikan']);
        Route::get('/peminjaman/riwayat', [PeminjamanController::class, 'index']); // Lihat riwayat sendiri
        Route::get('/mahasiswa/riwayat-saya', [PeminjamanController::class, 'riwayatMahasiswa']);
        Route::get('/mahasiswa/riwayat-ruang', [RuangController::class, 'riwayatRuang']);
        Route::post('/ruang/masuk', [RuangController::class, 'laporMasuk']); //lapor awal masuk ruang
        Route::post('/ruang/keluar/{id}', [RuangController::class, 'laporKeluar']); //lapor keluar ruang
    });

    });

    