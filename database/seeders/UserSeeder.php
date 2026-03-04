<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class UserSeeder extends Seeder
{
    public function run()
    {
        User::create([
            'name'     => 'Staf Admin Lab (Dummy)',
            'email'    => 'staf.dummy@staff.polines.ac.id',
            'nim_nip'  => '1987654321', 
            'role'     => 'staff',
            'password' => null, 
        ]);

        User::create([
            'name'     => 'Bapak Dosen (Dummy)',
            'email'    => 'dosen.dummy@dosen.polines.ac.id',
            'nim_nip'  => '1976050412', 
            'role'     => 'dosen',
            'password' => null,
        ]);
    }
}