<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Pastikan kolom sudah nullable sebelumnya jika diperlukan
            // Tambahkan unique constraint
            $table->unique('identity_number');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Hapus unique constraint jika migration di-rollback
            $table->dropUnique(['identity_number']);
        });
    }
};