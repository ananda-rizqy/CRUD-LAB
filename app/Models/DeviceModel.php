<?php

namespace App\Models;

use Database\Factories\DeviceModelFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeviceModel extends Model
{
    use HasFactory;
    protected $table = "table_devices";
    protected $fillable = ['device_names', 'mac_devices', 'rssi', 'tipe_device', 'status', 'x', 'y'];

    // protected static function newFactory()
    // {
    //     return DeviceModelFactory::new();
    // }
}
