<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
{
    Schema::table('peminjaman', function (Blueprint $table) {
        // Menambahkan kolom baru
        $table->dateTime('waktu_mulai')->nullable()->after('foto_before');
        $table->dateTime('waktu_selesai')->nullable()->after('waktu_mulai');
        $table->enum('jenis_peminjaman', ['langsung', 'pesanan'])->default('langsung')->after('waktu_selesai');
    });
}

public function down(): void
{
    Schema::table('peminjaman', function (Blueprint $table) {
        $table->dropColumn(['waktu_mulai', 'waktu_selesai', 'jenis_peminjaman']);
    });
}};
