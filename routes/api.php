    <?php

use App\Http\Controllers\Api\AlatController;
use App\Http\Controllers\Api\DeviceController;
use App\Http\Controllers\Api\PeminjamanController;
use App\Http\Controllers\Api\RuangController;
use Illuminate\Support\Facades\Route;

Route::apiResource('alat', AlatController::class);

// Device
Route::apiResource('device', DeviceController::class);

// fitur peminjaman
route::get('peminjaman', [PeminjamanController::class, 'index']);
route::post('peminjaman', [PeminjamanController::class, 'store']);
route::put('peminjaman/{id}/setujui', [PeminjamanController::class, 'setujui']);

Route::post('peminjaman/{id}/upload-foto', [PeminjamanController::class, 'uploadFotoBefore']);
Route::post('peminjaman/{id}/kembalikan', [PeminjamanController::class, 'kembalikan']);

// alat
Route::apiResource('alat', AlatController::class);

Route::post('ruang/masuk', [RuangController::class, 'masuk']);
Route::post('ruang/keluar/{id}', [RuangController::class, 'keluar']);
Route::get('ruang/riwayat', [RuangController::class, 'index']);
