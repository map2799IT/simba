<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;

class User extends Authenticatable
{
    use HasFactory;
    use Notifiable;
    use TwoFactorAuthenticatable;

    public const ROLE_ADMIN = 'admin';

    public const ROLE_WAKIL_SARPRAS = 'wakil_sarpras';

    public const ROLE_KEPALA_BENGKEL =
        'kepala_bengkel';

    public const ROLE_TOOLMAN = 'toolman';

    public const ROLE_GURU = 'guru';

    public const ROLE_SISWA = 'siswa';

    protected $fillable = [
        'name',
        'username',
        'nomor_identitas',
        'email',
        'phone',
        'email_verified_at',
        'password',
        'role',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    /*
     * Jangan menambahkan:
     *
     * protected $with = ['role'];
     *
     * Role adalah kolom string, bukan relationship.
     */

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    public static function roleOptions(): array
    {
        return [
            self::ROLE_ADMIN =>
                'Administrator',

            self::ROLE_WAKIL_SARPRAS =>
                'Wakil Sarana dan Prasarana',

            self::ROLE_KEPALA_BENGKEL =>
                'Kepala Bengkel',

            self::ROLE_TOOLMAN =>
                'Toolman',

            self::ROLE_GURU =>
                'Guru',

            self::ROLE_SISWA =>
                'Siswa',
        ];
    }

    public function hasRole(
        string ...$roles
    ): bool {
        return in_array(
            $this->role,
            $roles,
            true
        );
    }

    public function roleLabel(): string
    {
        return self::roleOptions()[$this->role]
            ?? ucfirst(
                str_replace(
                    '_',
                    ' ',
                    (string) $this->role
                )
            );
    }

    public function isActive(): bool
    {
        return (bool) $this->is_active;
    }

    public function workshopsHeaded(): HasMany
    {
        return $this->hasMany(
            Workshop::class,
            'head_user_id'
        );
    }

    public function borrowedLoans(): HasMany
    {
        return $this->hasMany(
            Loan::class,
            'borrower_id'
        );
    }

    public function approvedLoans(): HasMany
    {
        return $this->hasMany(
            Loan::class,
            'approved_by'
        );
    }

    public function reportedDamages(): HasMany
    {
        return $this->hasMany(
            DamageReport::class,
            'reported_by'
        );
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(
            ItemStockMovement::class,
            'user_id'
        );
    }

    public function workshop():
        \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(
            \App\Models\Workshop::class,
            'workshop_id',
            'id'
        );
    }
}