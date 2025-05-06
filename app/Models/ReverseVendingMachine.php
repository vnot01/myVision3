<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany; // Untuk relasi deposit

class ReverseVendingMachine extends Model
{
    use HasFactory;

    protected $table = 'reverse_vending_machines'; // Nama tabel eksplisit (opsional jika nama model jamak == nama tabel)

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'location',
        'latitude',
        'longitude',
        'status',
        'api_key', // Hati-hati jika ingin mass assignable
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7', // Cast ke decimal dengan presisi
            'longitude' => 'decimal:7',
            // Enum tidak perlu cast eksplisit di Laravel 10+ tapi bisa ditambahkan
            // 'status' => 'string', // Atau buat Enum class jika ingin lebih ketat
        ];
    }

    /**
     * Mendefinisikan relasi bahwa satu RVM memiliki banyak deposit.
     */
    public function deposits(): HasMany
    {
        return $this->hasMany(Deposit::class, 'rvm_id'); // 'rvm_id' adalah foreign key di tabel deposits
    }
}