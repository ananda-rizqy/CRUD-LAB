<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
        public function up()
    {
        Schema::table('peminjaman', function (Blueprint $table) {
            $table->string('foto_before')->nullable()->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
        public function down()
    {
        Schema::table('peminjaman', function (Blueprint $table) {
            $table->dropColumn('foto_before');
        });
    }
};
