<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ItemStockMovement extends Model
{
    public const TYPE_INITIAL = 'initial';
    public const TYPE_INCOMING = 'incoming';
    public const TYPE_OUTGOING = 'outgoing';
    public const TYPE_ADJUSTMENT_IN = 'adjustment_in';
    public const TYPE_ADJUSTMENT_OUT = 'adjustment_out';
    public const TYPE_LOAN = 'loan';
    public const TYPE_RETURN = 'return';

    protected $fillable = [
        'receipt_code',
        'item_id',
        'user_id',
        'workshop_id',
        'storage_location_id',
        'type',
        'quantity',
        'stock_before',
        'stock_after',
        'transaction_date',
        'reference_number',
        'source',
        'brand',
        'model',
        'specification',
        'fund_source',
        'unit_price',
        'condition',
        'photo_path',
        'destination',
        'purpose',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
            'stock_before' => 'decimal:3',
            'stock_after' => 'decimal:3',
            'transaction_date' => 'date',
            'unit_price' => 'decimal:2',
        ];
    }

    public static function typeOptions(): array
    {
        return [
            self::TYPE_INITIAL =>
                'Saldo Awal',

            self::TYPE_INCOMING =>
                'Barang Masuk',

            self::TYPE_OUTGOING =>
                'Barang Keluar',

            self::TYPE_ADJUSTMENT_IN =>
                'Penyesuaian Bertambah',

            self::TYPE_ADJUSTMENT_OUT =>
                'Penyesuaian Berkurang',

            self::TYPE_LOAN =>
                'Peminjaman',

            self::TYPE_RETURN =>
                'Pengembalian',
        ];
    }

    public function typeLabel(): string
    {
        return self::typeOptions()[
            $this->type
        ] ?? ucfirst(
            (string) $this->type
        );
    }

    public function difference(): float
    {
        return round(
            (float) $this->stock_after
            - (float) $this->stock_before,
            3
        );
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

    public function user(): BelongsTo
    {
        return $this->belongsTo(
            User::class
        );
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

    public function changeRequests(): HasMany
    {
        return $this->hasMany(
            StockReceiptChangeRequest::class,
            'item_stock_movement_id'
        );
    }

    public function pendingChangeRequest(): HasOne
    {
        return $this->hasOne(
            StockReceiptChangeRequest::class,
            'item_stock_movement_id'
        )
            ->where(
                'status',
                StockReceiptChangeRequest::
                    STATUS_PENDING
            )
            ->latestOfMany();
    }

    public function pendingIssueChangeRequest(): HasOne
    {
        return $this->hasOne(
            StockIssueChangeRequest::class,
            'item_stock_movement_id'
        )
            ->where('status', StockIssueChangeRequest::STATUS_PENDING)
            ->latestOfMany();
    }
}
