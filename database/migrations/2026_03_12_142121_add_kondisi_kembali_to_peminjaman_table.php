<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
{
    Schema::table('peminjaman', function (Blueprint $table) {
        // Menambahkan kolom untuk mencatat kerusakan
        $table->string('kondisi_kembali')->nullable()->after('status');
        $table->text('deskripsi_kerusakan')->nullable()->after('kondisi_kembali');
    });
}

public function down(): void
{
    Schema::table('peminjamans', function (Blueprint $table) {
        $table->dropColumn(['kondisi_kembali', 'deskripsi_kerusakan']);
    });
}
};
