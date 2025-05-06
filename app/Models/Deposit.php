<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo; // Untuk relasi User dan RVM

class Deposit extends Model
{
    use HasFactory;

    protected $table = 'deposits';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'rvm_id',
        'detected_type',
        'points_awarded',
        'needs_action',
        'deposited_at', // Bisa fillable jika ingin set manual, atau biarkan default DB
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'points_awarded' => 'integer',
            'needs_action' => 'boolean',
            'deposited_at' => 'datetime',
            // Enum tidak perlu cast eksplisit di Laravel 10+
            // 'detected_type' => 'string', // Atau buat Enum class
        ];
    }

    /**
     * Mendefinisikan relasi bahwa deposit ini milik satu User.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id'); // Foreign key 'user_id'
    }

    /**
     * Mendefinisikan relasi bahwa deposit ini dilakukan di satu RVM.
     */
    public function rvm(): BelongsTo // Nama method bisa 'rvm' atau 'machine' dll.
    {
        return $this->belongsTo(ReverseVendingMachine::class, 'rvm_id'); // Foreign key 'rvm_id'
    }
}