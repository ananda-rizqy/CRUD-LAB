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
            $table->foreignId('alat_id')->constrained('alats')->onDelete('cascade');
            $table->text('tujuan_penggunaan');
            $table->enum('status', ['pending', 'approved', 'ongoing', 'returned', 'rejected'])->default('pending');
            $table->string('foto_before')->nullable(); 
            $table->string('foto_after')->nullable();  
            $table->enum('kondisi_kembali', ['baik', 'rusak'])->nullable();
            $table->text('deskripsi_kerusakan')->nullable(); 
            $table->dateTime('waktu_pinjam');     
            $table->dateTime('waktu_kembali');   
            $table->dateTime('tanggal_diambil')->nullable();  
            $table->dateTime('tanggal_kembali')->nullable(); 
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('peminjaman');
    }
};
