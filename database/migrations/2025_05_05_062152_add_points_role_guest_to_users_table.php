<?php
// database/migrations/xxxx_xx_xx_xxxxxx_add_points_role_guest_to_users_table.php

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
        Schema::table('users', function (Blueprint $table) {
            // Tambahkan setelah kolom yang sudah ada (misal setelah identity_number)
            $table->integer('points')->default(0)->after('identity_number');
            $table->enum('role', ['Admin', 'Operator', 'User'])->default('User')->after('points');
            $table->boolean('is_guest')->default(false)->after('role');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Urutan drop biasanya dibalik dari penambahan
            $table->dropColumn(['is_guest', 'role', 'points']);
        });
    }
};