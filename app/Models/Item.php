<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Item extends Model
{
    use HasFactory;

    /**
     * Resolve route binding tanpa global scope agar admin dan toolman
     * dapat mengakses item dari semua jurusan melalui URL.
     */
    public function resolveRouteBinding($value, $field = null): ?self
    {
        return $this->withoutGlobalScopes()
            ->where($field ?? $this->getRouteKeyName(), $value)
            ->first();
    }

    public const TYPE_OPTIONS = [
        'tool' => 'Alat',
        'material' => 'Bahan',
    ];

    public const CONDITION_OPTIONS = [
        'good' => 'Baik',
        'minor_damage' => 'Rusak Ringan',
        'major_damage' => 'Rusak Berat',
        'maintenance' => 'Dalam Perawatan',
        'unfit' => 'Tidak Layak Pakai',
    ];

    public const STATUS_OPTIONS = [
        'available' => 'Tersedia',
        'reserved' => 'Dipesan',
        'borrowed' => 'Dipinjam',
        'damaged' => 'Rusak',
        'maintenance' => 'Dalam Perawatan',
        'lost' => 'Hilang',
        'retired' => 'Dihapuskan',
        'out_of_stock' => 'Stok Habis',
    ];

    protected $fillable = [
        'type',
        'code',
        'name',
        'item_category_id',
        'unit_id',
        'workshop_id',
        'storage_location_id',
        'brand',
        'model',
        'serial_number',
        'specification',
        'received_date',
        'acquisition_source',
        'fund_source',
        'unit_price',
        'condition',
        'status',
        'stock',
        'minimum_stock',
        'is_borrowable',
        'photo_path',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'received_date' => 'date',
            'unit_price' => 'decimal:2',
            'stock' => 'decimal:3',
            'minimum_stock' => 'decimal:3',
            'is_borrowable' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public static function typeOptions(): array
    {
        return self::TYPE_OPTIONS;
    }

    public static function conditionOptions(): array
    {
        return self::CONDITION_OPTIONS;
    }

    public static function statusOptions(): array
    {
        return self::STATUS_OPTIONS;
    }

    public function typeLabel(): string
    {
        return self::TYPE_OPTIONS[
            $this->type
        ] ?? (string) $this->type;
    }

    public function conditionLabel(): string
    {
        return self::CONDITION_OPTIONS[
            $this->condition
        ] ?? (string) $this->condition;
    }

    public function statusLabel(): string
    {
        return self::STATUS_OPTIONS[
            $this->status
        ] ?? (string) $this->status;
    }

    public function isTool(): bool
    {
        return $this->type === 'tool';
    }

    public function isMaterial(): bool
    {
        return $this->type === 'material';
    }

    public function isLowStock(): bool
    {
        return $this->isMaterial()
            && (float) $this->stock
                <= (float) $this->minimum_stock;
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(
            ItemCategory::class,
            'item_category_id'
        );
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(
            Unit::class
        );
    }

    public function workshop(): BelongsTo
    {
        return $this->belongsTo(
            Workshop::class
        );
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(
            StorageLocation::class,
            'storage_location_id'
        );
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(
            ItemStockMovement::class
        );
    }

    public function itemAssets(): HasMany
    {
        return $this->hasMany(
            ItemAsset::class
        );
    }

    public function loanItems(): HasMany
    {
        return $this->hasMany(
            LoanItem::class
        );
    }

    public function damageReports(): HasMany
    {
        return $this->hasMany(
            DamageReport::class
        );
    }
}
