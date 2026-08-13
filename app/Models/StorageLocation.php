<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StorageLocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'workshop_id',
        'parent_id',
        'code',
        'name',
        'type',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public static function typeOptions(): array
    {
        return [
            'room' => 'Ruangan',
            'cabinet' => 'Lemari',
            'shelf' => 'Rak',
            'drawer' => 'Laci',
            'box' => 'Kotak',
            'other' => 'Lainnya',
        ];
    }

    public function workshop(): BelongsTo
    {
        return $this->belongsTo(
            Workshop::class
        );
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(
            self::class,
            'parent_id'
        );
    }

    public function children(): HasMany
    {
        return $this->hasMany(
            self::class,
            'parent_id'
        );
    }

    public function items(): HasMany
    {
        return $this->hasMany(
            Item::class,
            'storage_location_id'
        );
    }

    public function itemAssets(): HasMany
    {
        return $this->hasMany(
            ItemAsset::class,
            'storage_location_id'
        );
    }

    public function typeLabel(): string
    {
        return self::typeOptions()[
            (string) $this->type
        ] ?? ucfirst(
            str_replace(
                '_',
                ' ',
                (string) $this->type
            )
        );
    }

    public function breadcrumb(): string
    {
        $parts = [
            $this->code.' — '.$this->name,
        ];

        $parent = $this->parent;
        $guard = 0;

        while (
            $parent !== null
            && $guard < 10
        ) {
            array_unshift(
                $parts,
                $parent->code.' — '.$parent->name
            );

            $parent = $parent->parent;
            $guard++;
        }

        return implode(
            ' / ',
            $parts
        );
    }
}
