<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('peminjaman', function (Blueprint $table) {
            $table->id();
            $table->foreignId('alat_id')->constrained('alats')->onDelete('cascade'); // Relasi ke tabel alat
            $table->string('nama_mahasiswa'); // Identitas Mahasiswa
            $table->string('nim');            // Identitas Mahasiswa
            $table->string('laboratorium');   // Lokasi Lab
            $table->string('tujuan_penggunaan'); 
            $table->dateTime('waktu_pinjam'); 
            $table->dateTime('waktu_kembali'); 
            $table->string('status')->default('pending'); // Status awal
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('peminjaman');
    }
};
