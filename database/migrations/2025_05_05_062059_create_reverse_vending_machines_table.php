<?php
// database/migrations/xxxx_xx_xx_xxxxxx_create_reverse_vending_machines_table.php

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
        Schema::create('reverse_vending_machines', function (Blueprint $table) {
            $table->id(); // Primary Key (BigInt unsigned)
            $table->string('name'); // Nama/Identifikasi RVM
            $table->text('location')->nullable(); // Deskripsi lokasi
            $table->decimal('latitude', 10, 7)->nullable(); // Latitude (presisi 10 total, 7 di belakang koma)
            $table->decimal('longitude', 10, 7)->nullable(); // Longitude (presisi 10 total, 7 di belakang koma)
            $table->enum('status', ['active', 'inactive', 'maintenance'])->default('active'); // Status RVM
            $table->string('api_key')->unique()->nullable(); // Kunci API untuk otentikasi RVM (opsional)
            $table->timestamps(); // created_at dan updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reverse_vending_machines');
    }
};