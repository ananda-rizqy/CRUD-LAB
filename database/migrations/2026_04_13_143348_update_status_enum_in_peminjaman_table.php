<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB; // Tambahkan ini

return new class extends Migration
{
    public function up(): void
    {
        // Menambahkan 'approved' ke dalam daftar ENUM
        DB::statement("ALTER TABLE peminjaman MODIFY COLUMN status ENUM('menunggu', 'disetujui', 'berlangsung', 'selesai', 'ditolak', 'dipesan') DEFAULT 'menunggu'");
    }

    public function down(): void
    {
        // Kembalikan ke kondisi sebelumnya jika diperlukan
        DB::statement("ALTER TABLE peminjaman MODIFY COLUMN status ENUM('menunggu', 'berlangsung', 'selesai', 'ditolak', 'dipesan') DEFAULT 'menunggu'");
    }
};