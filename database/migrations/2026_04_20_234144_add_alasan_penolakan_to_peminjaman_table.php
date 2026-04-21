<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('peminjaman', function (Blueprint $table) {
            $table->text('alasan_penolakan')->nullable()->after('status');
            $table->foreignId('penerima_id')->nullable()->after('alasan_penolakan')->constrained('users');
        });
    }

    public function down(): void
    {
        Schema::table('peminjaman', function (Blueprint $table) {
            $table->dropForeign(['penerima_id']);
            $table->dropColumn(['alasan_penolakan', 'penerima_id']);
        });
    }
};