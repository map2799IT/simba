<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoanItemReplacementRequest extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_FULFILLED = 'fulfilled';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'loan_id',
        'loan_item_id',
        'item_id',
        'old_asset_id',
        'new_asset_id',
        'requested_by',
        'handled_by',
        'status',
        'damage_description',
        'replacement_asset_code',
        'notes',
        'handled_at',
    ];

    protected function casts(): array
    {
        return [
            'handled_at' => 'datetime',
        ];
    }

    public static function statusOptions(): array
    {
        return [
            self::STATUS_PENDING => 'Menunggu Penggantian',
            self::STATUS_FULFILLED => 'Sudah Diganti',
            self::STATUS_CANCELLED => 'Dibatalkan',
        ];
    }

    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class);
    }

    public function loanItem(): BelongsTo
    {
        return $this->belongsTo(LoanItem::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class)->withoutGlobalScopes();
    }

    public function oldAsset(): BelongsTo
    {
        return $this->belongsTo(ItemAsset::class, 'old_asset_id')->withoutGlobalScopes();
    }

    public function newAsset(): BelongsTo
    {
        return $this->belongsTo(ItemAsset::class, 'new_asset_id')->withoutGlobalScopes();
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function handler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }
}
