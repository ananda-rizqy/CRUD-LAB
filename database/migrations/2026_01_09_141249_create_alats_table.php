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
            $table->string('nama_alat');      
            $table->string('letak'); 
            $table->string('kode_tag')->unique()->nullable();
            $table->integer('jumlah')->default(1);
            $table->enum('kondisi', ['Baik', 'Rusak'])->default('Baik');  
            $table->boolean('is_aset')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alats');
    }
};