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
        Schema::create('table_devices', function (Blueprint $table) {
            $table->id();
            $table->string('device_names', 100);
            $table->string('mac_devices', 100);
            $table->integer('rssi')->nullable();
            $table->string('tipe_device', 10);
            $table->tinyInteger('status')->default(1)->nullable();
            $table->string('x', 10)->nullable();
            $table->string('y', 10)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('table_devices');
    }
};
