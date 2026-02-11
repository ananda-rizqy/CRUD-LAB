<?php

use App\Events\DeviceEvent;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/test-broadcast', function () {
    event(new DeviceEvent([
        'id' => 999,
        'device_names' => 'TEST REALTIME 🔥',
        'mac_devices' => '00:11:22:33',
        'rssi' => -50,
        'tipe_device' => true,
        'status' => true,
        'x' => 10,
        'y' => 20,
        'time_stamp' => time(),
    ]));

    return 'Event sent!';
});
