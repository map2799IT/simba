<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Workshop extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function scopeActive(
        Builder $query
    ): Builder {
        return $query->where(
            'is_active',
            true
        );
    }

    public function scopeOrdered(
        Builder $query
    ): Builder {
        return $query
            ->orderBy('code')
            ->orderBy('name');
    }

    public function users(): HasMany
    {
        return $this->hasMany(
            User::class,
            'workshop_id'
        );
    }

    public function items(): HasMany
    {
        return $this->hasMany(
            Item::class,
            'workshop_id'
        );
    }

    public function itemAssets(): HasMany
    {
        return $this->hasMany(
            ItemAsset::class,
            'workshop_id'
        );
    }

    public function storageLocations(): HasMany
    {
        return $this->hasMany(
            StorageLocation::class,
            'workshop_id'
        );
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->code.
            ' — '.
            $this->name;
    }

    protected function setCodeAttribute(
        mixed $value
    ): void {
        $this->attributes['code'] =
            strtoupper(
                trim(
                    (string) $value
                )
            );
    }

    protected function setNameAttribute(
        mixed $value
    ): void {
        $this->attributes['name'] =
            trim(
                (string) $value
            );
    }
}
