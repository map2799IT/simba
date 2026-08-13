<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ItemAsset extends Model
{
    use HasFactory;

    /**
     * Resolve route binding tanpa global scope agar admin dan user
     * lintas jurusan dapat mengakses unit alat melalui URL.
     */
    public function resolveRouteBinding($value, $field = null): ?self
    {
        return $this->withoutGlobalScopes()
            ->where($field ?? $this->getRouteKeyName(), $value)
            ->first();
    }

    public const STATUS_AVAILABLE = 'available';
    public const STATUS_RESERVED = 'reserved';
    public const STATUS_BORROWED = 'borrowed';
    public const STATUS_DAMAGED = 'damaged';
    public const STATUS_UNDER_REPAIR = 'under_repair';
    public const STATUS_LOST = 'lost';
    public const STATUS_RETIRED = 'retired';

    public const CONDITION_GOOD = 'good';
    public const CONDITION_MINOR_DAMAGE = 'minor_damage';
    public const CONDITION_MAJOR_DAMAGE = 'major_damage';

    protected $fillable = [
        'item_id',
        'asset_number',
        'barcode_value',
        'receipt_code',
        'serial_number',
        'brand',
        'model',
        'specification',
        'acquisition_source',
        'fund_source',
        'workshop_id',
        'storage_location_id',
        'condition',
        'status',
        'received_date',
        'unit_price',
        'photo_path',
        'notes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'received_date' => 'date',
            'unit_price' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public static function statusOptions(): array
    {
        return [
            self::STATUS_AVAILABLE =>
                'Tersedia',

            self::STATUS_RESERVED =>
                'Dipesan',

            self::STATUS_BORROWED =>
                'Dipinjam',

            self::STATUS_DAMAGED =>
                'Rusak',

            self::STATUS_UNDER_REPAIR =>
                'Dalam Perbaikan',

            self::STATUS_LOST =>
                'Hilang',

            self::STATUS_RETIRED =>
                'Dihapuskan',
        ];
    }

    public static function conditionOptions(): array
    {
        return [
            self::CONDITION_GOOD =>
                'Baik',

            self::CONDITION_MINOR_DAMAGE =>
                'Rusak Ringan',

            self::CONDITION_MAJOR_DAMAGE =>
                'Rusak Berat',
        ];
    }

    public function item(): BelongsTo
    {
        $relation =
            $this->belongsTo(
                Item::class
            );

        $relation
            ->getQuery()
            ->withoutGlobalScopes();

        return $relation;
    }

    public function workshop(): BelongsTo
    {
        return $this->belongsTo(
            Workshop::class
        );
    }

    public function storageLocation(): BelongsTo
    {
        return $this->belongsTo(
            StorageLocation::class
        );
    }

    public function loanItems(): HasMany
    {
        return $this->hasMany(
            LoanItem::class,
            'item_asset_id'
        );
    }

    public function damageReports(): HasMany
    {
        return $this->hasMany(
            DamageReport::class,
            'item_asset_id'
        );
    }

    public function scopeAvailable(
        Builder $query
    ): Builder {
        return $query
            ->where(
                'is_active',
                true
            )
            ->where(
                'status',
                self::STATUS_AVAILABLE
            );
    }

    public function statusLabel(): string
    {
        return self::statusOptions()[
            $this->status
        ] ?? ucfirst(
            str_replace(
                '_',
                ' ',
                (string) $this->status
            )
        );
    }

    public function conditionLabel(): string
    {
        return self::conditionOptions()[
            $this->condition
        ] ?? ucfirst(
            str_replace(
                '_',
                ' ',
                (string) $this->condition
            )
        );
    }
}
