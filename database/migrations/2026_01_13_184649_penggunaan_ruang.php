<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('penggunaan_ruang', function (Blueprint $table) {
            //kondisi awal ruang
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('laboratorium');
            $table->string('kondisi_masuk');
            $table->string('foto_before'); 
            $table->timestamp('waktu_masuk')->useCurrent();
            $table->string('keperluan');
           
            //kondisi akhir ruang
            $table->string('kondisi_keluar')->nullable();
            $table->string('foto_after')->nullable(); 
            $table->timestamp('waktu_keluar')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {

        Schema::table('penggunaan_ruang', function (Blueprint $table) {
        $table->dropForeign(['user_id']); 
        });
        Schema::dropIfExists('penggunaan_ruang');
    }
};