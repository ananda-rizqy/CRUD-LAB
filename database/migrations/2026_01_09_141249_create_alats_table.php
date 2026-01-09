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
        Schema::create('alats', function (Blueprint $table) {
            $table->id();
            $table->string('nama_alat');      // Tambahkan ini [cite: 31, 151]
            $table->string('ruang_lab'); // Tambahkan ini [cite: 31, 151]
            $table->integer('total');          // Tambahkan ini [cite: 31, 151]
            $table->integer('tersedia');       // Tambahkan ini [cite: 31, 151]
            $table->string('kondisi');         // Tambahkan ini [cite: 31, 151]
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alats');
    }
};
