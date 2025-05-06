<?php

namespace App\Models;

// Import Kontrak MustVerifyEmail
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Sanctum\HasApiTokens;

// Implementasikan MustVerifyEmail
class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'google_id',
        'avatar',
        'phone_number', // Tetap simpan nomor HP
        // Hapus 'phone_code', 'phone_verified_at' dari fillable jika ada
        'citizenship',
        'identity_type',
        'identity_number',
        'points',       // <-- Tambahkan
        'role',         // <-- Tambahkan
        'is_guest',     // <-- Tambahkan
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'password',
        'remember_token',
        'google_id',
        // Hapus 'phone_code' dari hidden jika ada
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime', // Pastikan ini ada
            // Hapus 'phone_verified_at' dari casts jika ada
            'password' => 'hashed',
            'is_guest' => 'boolean', // <-- Tambahkan cast boolean
            // 'role' => 'string',    // <-- Bisa tambahkan cast Enum jika dibuat
        ];
    }

    // HAPUS METODE INI KARENA TIDAK DIGUNAKAN LAGI UNTUK VERIFIKASI UTAMA
    // public function hasVerifiedPhone(): bool
    // {
    //     return ! is_null($this->phone_verified_at);
    // }

    /**
     * Mendefinisikan relasi one-to-many ke Deposit.
     * Seorang User bisa memiliki banyak Deposit.
     */
    public function deposits(): HasMany // Tentukan return type
    {
        return $this->hasMany(Deposit::class);
    }
}
