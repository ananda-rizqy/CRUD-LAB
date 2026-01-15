<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AlatSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('alats')->insert([
            [
                'nama_alat' => 'Router Mikrotik',
                'ruang_lab' => 'Lab Jaringan',
                'total' => 10,
                'tersedia' => 8,
                'kondisi' => 'baik',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nama_alat' => 'Switch TP-Link',
                'ruang_lab' => 'Lab Komputer',
                'total' => 5,
                'tersedia' => 5,
                'kondisi' => 'baik',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nama_alat' => 'Access Point Ubiquiti',
                'ruang_lab' => 'Lab Multimedia',
                'total' => 7,
                'tersedia' => 6,
                'kondisi' => 'baik',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
