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
            $table->string('nim_nip')->unique()->nullable(); // ID unik (NIM/NIP)
            $table->string('email')->unique()->nullable();
            $table->enum('role', ['mahasiswa', 'dosen', 'staff'])->default('mahasiswa');
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