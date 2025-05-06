<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('password')->nullable()->change(); // Buat password nullable untuk Google Sign In
            $table->string('google_id')->nullable()->unique()->after('id');
            $table->string('avatar')->nullable()->after('email'); // Menyimpan URL avatar Google
            $table->string('phone_number')->nullable()->after('avatar');
            $table->string('phone_code')->nullable()->after('phone_number'); // Kode verifikasi 6 digit
            $table->timestamp('phone_verified_at')->nullable()->after('phone_code');
            $table->enum('citizenship', ['WNI', 'WNA'])->nullable()->after('phone_verified_at');
            $table->enum('identity_type', ['KTP', 'Pasport'])->nullable()->after('citizenship');
            $table->string('identity_number')->nullable()->after('identity_type');

            // Jika Anda menggunakan remember_token default, pastikan kolom lain ditambahkan SEBELUMnya
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Hati-hati: Buat password non-nullable lagi bisa menyebabkan error jika ada user Google
            // $table->string('password')->nullable(false)->change();

            $table->dropColumn([
                'google_id',
                'avatar',
                'phone_number',
                'phone_code',
                'phone_verified_at',
                'citizenship',
                'identity_type',
                'identity_number',
            ]);
        });
    }
};
