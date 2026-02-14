<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Menambahkan dropIfExists agar tidak bentrok saat proses migrate
        Schema::dropIfExists('users');

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('nim')->unique()->nullable(); // ID unik (NIM/NIP)
            $table->string('email')->unique()->nullable();
            $table->string('prodi')->nullable(); // D3/D4 Telkom
            $table->enum('role', ['mahasiswa', 'dosen', 'staff'])->default('mahasiswa');
            $table->string('kelas')->nullable(); // Contoh: IK-3A
            $table->string('password')->nullable(); // Nullable untuk persiapan SSO
            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};