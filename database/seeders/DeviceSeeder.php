<?php

namespace Database\Seeders;

use App\Models\DeviceModel;
use Illuminate\Database\Seeder;

class DeviceSeeder extends Seeder
{
    public function run(): void
    {
        DeviceModel::factory(100)->create();
    }
}
