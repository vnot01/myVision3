<?php
// database/migrations/xxxx_xx_xx_xxxxxx_create_deposits_table.php

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
        Schema::create('deposits', function (Blueprint $table) {
            $table->id(); // Primary Key
            // Foreign key ke users. id() membuat bigInt unsigned.
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // User yang melakukan deposit
            // Foreign key ke reverse_vending_machines
            $table->foreignId('rvm_id')->constrained('reverse_vending_machines')->onDelete('cascade'); // RVM tempat deposit
            $table->enum('detected_type', ['mineral_plastic', 'other_bottle', 'unknown', 'contains_content']); // Hasil deteksi
            $table->integer('points_awarded')->default(0); // Poin yang diberikan
            $table->boolean('needs_action')->default(false); // Apakah botol perlu diambil lagi?
            $table->timestamp('deposited_at')->useCurrent(); // Waktu deposit
            $table->timestamps(); // created_at dan updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deposits');
    }
};