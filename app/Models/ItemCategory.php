<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ItemCategory extends Model
{
    use HasFactory;

    public const APPLIES_TO_OPTIONS = [
        'tool' => 'Alat',
        'material' => 'Bahan',
        'both' => 'Alat dan Bahan',
    ];

    protected $fillable = [
        'code',
        'name',
        'applies_to',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public static function appliesToOptions(): array
    {
        return self::APPLIES_TO_OPTIONS;
    }

    public function appliesToLabel(): string
    {
        return self::APPLIES_TO_OPTIONS[$this->applies_to] ?? $this->applies_to;
    }
    public function items(): HasMany
    {
        return $this->hasMany(
            Item::class,
            'item_category_id'
        );
    }
}
