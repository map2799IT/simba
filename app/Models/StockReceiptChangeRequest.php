<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockReceiptChangeRequest extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'item_stock_movement_id',
        'requested_by_user_id',
        'reviewed_by_user_id',
        'status',
        'original_payload',
        'requested_payload',
        'request_note',
        'review_note',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'original_payload' => 'array',
            'requested_payload' => 'array',
            'reviewed_at' => 'datetime',
        ];
    }

    public static function statusOptions(): array
    {
        return [
            self::STATUS_PENDING => 'Menunggu Persetujuan',
            self::STATUS_APPROVED => 'Disetujui',
            self::STATUS_REJECTED => 'Ditolak',
        ];
    }

    public function statusLabel(): string
    {
        return self::statusOptions()[$this->status]
            ?? ucfirst(str_replace('_', ' ', (string) $this->status));
    }

    public function movement(): BelongsTo
    {
        return $this->belongsTo(
            ItemStockMovement::class,
            'item_stock_movement_id'
        );
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_user_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }
}
