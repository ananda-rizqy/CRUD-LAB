    <?php

    use App\Http\Controllers\Api\AuthController;
    use App\Http\Controllers\Api\AlatController;
    // use App\Http\Controllers\Api\PeminjamanController;
    // use App\Http\Controllers\Api\RuangController;
    use App\Http\Controllers\Api\DeviceController;
    use Illuminate\Support\Facades\Route;

    // Rute Login
    Route::post('/login', [AuthController::class, 'login']);

    //fitur CRUD alat
    Route::apiResource('alat', AlatController::class);

    // Device
    Route::apiResource("device", DeviceController::class);

    // //fitur peminjaman dan pengembalian alat
    // route::post('peminjaman', [PeminjamanController::class, 'store']);
    // route::get('peminjaman', [PeminjamanController::class, 'index']);
    // route::put('peminjaman/{id}/setujui', [PeminjamanController::class, 'setujui']);
    // Route::post('peminjaman/{id}/upload-foto', [PeminjamanController::class, 'uploadFotoBefore']);
    // Route::post('peminjaman/{id}/kembalikan', [PeminjamanController::class, 'kembalikan']);

    // //fitur penggunaan lab
    // Route::post('ruang/masuk', [RuangController::class, 'masuk']);
    // Route::post('ruang/keluar/{id}', [RuangController::class, 'keluar']);
    // Route::get('ruang', [RuangController::class, 'index']);

    
