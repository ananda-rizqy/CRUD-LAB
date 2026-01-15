<?php

namespace Database\Factories;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Peminjaman>
 */
class PeminjamanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $waktuPinjam = $this->faker->dateTimeBetween('-5 days', 'now');
        $waktuKembali = Carbon::instance($waktuPinjam)->addDays(rand(1, 5));

        return [
            'alat_id' => 1, // pastikan alat dengan id 1 ada
            'nama_mahasiswa' => $this->faker->name(),
            'nim' => $this->faker->numerify('21########'),
            'laboratorium' => $this->faker->randomElement([
                'Lab Jaringan',
                'Lab Multimedia',
                'Lab Komputer',
                'Lab Elektronika',
            ]),
            'tujuan_penggunaan' => $this->faker->sentence(3),
            'waktu_pinjam' => $waktuPinjam,
            'waktu_kembali' => $waktuKembali,
            'status' => $this->faker->randomElement([
                'pending',
                'approved',
                'rejected',
                'returned',
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
