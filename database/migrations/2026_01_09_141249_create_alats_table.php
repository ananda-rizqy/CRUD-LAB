<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create('alats', function (Blueprint $table) {
            $table->id();
            $table->string('qrcode_token')->unique(); 
            $table->string('nama_alat');      
            $table->string('letak'); 
            $table->string('kode')->unique()->nullable();
            $table->enum('kondisi', ['Baik', 'Rusak'])->default('Baik');  
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alats');
    }
};