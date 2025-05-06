<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;

class DatabaseSeeder extends Seeder
{

    protected static ?string $password;
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        // User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'points' => 11111,
            'role' => 'Admin',
            'identity_number' => '3375012502890001',
            'citizenship' => 'WNI',
            'identity_type' => 'KTP',
        ]);
        
        DB::table('reverse_vending_machines')->insert([
            'name' => fake()->name(),
            'location' => fake()->streetAddress(),
            'latitude' => fake()->latitude(),
            'longitude' => fake()->longitude(),
            'status' => 'active',
            'api_key' => 'e2c81eaaab8b0df7302732db8c85b545b592d5f2aac3fca82b2e20a3e209882e',
        ]);
        
        // $table->string('name'); // Nama/Identifikasi RVM
        // $table->text('location')->nullable(); // Deskripsi lokasi
        // $table->decimal('latitude', 10, 7)->nullable(); // Latitude (presisi 10 total, 7 di belakang koma)
        // $table->decimal('longitude', 10, 7)->nullable(); // Longitude (presisi 10 total, 7 di belakang koma)
        // $table->enum('status', ['active', 'inactive', 'maintenance'])->default('active'); // Status RVM
        // $table->string('api_key')->unique()->nullable(); // Kunci API untuk otentikasi RVM (opsional)

        // $table->string('name');
        // $table->string('email')->unique();
        // $table->timestamp('email_verified_at')->nullable()->after('email');
        // $table->string('password');
        // $table->integer('points')->default(0)->after('identity_number');
        // $table->enum('role', ['Admin', 'Operator', 'User'])->default('User')->after('points');
        // $table->boolean('is_guest')->default(false)->after('role');
        // $table->unique('identity_number');
        // $table->enum('citizenship', ['WNI', 'WNA'])->nullable()->after('phone_verified_at');
        // $table->enum('identity_type', ['KTP', 'Pasport'])->nullable()->after('citizenship');
    }
}
