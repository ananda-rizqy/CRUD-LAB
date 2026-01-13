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
        Schema::create('penggunaan_ruang', function (Blueprint $table) {
            $table->id();
            $table->string('nama_mahasiswa');
            $table->string('nim');
            $table->string('laboratorium');
            $table->string('keperluan');
            $table->string('foto_before')->nullable(); 
            $table->string('foto_after')->nullable();
            
            // PERBAIKAN: Hapus ->after('foto_after')
            $table->string('kondisi_lab')->nullable(); 
            
            $table->timestamp('waktu_masuk')->useCurrent();
            $table->timestamp('waktu_keluar')->nullable();
            $table->enum('status', ['aktif', 'selesai'])->default('aktif');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('penggunaan_ruang');
    }
};