<?php

use App\Http\Controllers\Api\AlatController;
use Illuminate\Support\Facades\Route;

Route::apiResource('alat', AlatController::class);