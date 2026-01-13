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
        Schema::table('peminjaman', function (Blueprint $table) {
            // Menambah kolom kondisi dan deskripsi aduan kerusakan
            $table->string('kondisi_kembali')->nullable()->after('foto_after');
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
