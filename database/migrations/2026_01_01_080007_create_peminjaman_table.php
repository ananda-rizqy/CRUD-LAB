<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
 
    public function up(): void
    {
        Schema::create('peminjaman', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('ruangan_lab');
            $table->text('tujuan_penggunaan');
            $table->enum('status', ['menunggu', 'disetujui', 'berlangsung', 'selesai', 'ditolak', 'dipesan'])->default('menunggu');
            $table->string('foto_before')->nullable(); 
            $table->string('foto_after')->nullable();  
            $table->dateTime('waktu_pinjam')->nullable();
            $table->dateTime('waktu_kembali')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('peminjaman');
    }
};
