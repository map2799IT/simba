<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Loan extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_BORROWED = 'borrowed';
    public const STATUS_PARTIAL = 'partially_returned';
    public const STATUS_RETURNED = 'returned';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'code',
        'borrower_id',
        'workshop_id',
        'assigned_toolman_id',
        'approved_by',
        'rejected_by',
        'returned_by',
        'status',
        'request_date',
        'scheduled_at',
        'due_at',
        'extended_due_at',
        'extended_by',
        'extension_reason',
        'extended_at',
        'approved_at',
        'borrowed_at',
        'rejected_at',
        'returned_at',
        'purpose',
        'notes',
        'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'request_date' => 'date',
            'scheduled_at' => 'datetime',
            'due_at' => 'datetime',
            'extended_due_at' => 'datetime',
            'extended_at' => 'datetime',
            'approved_at' => 'datetime',
            'borrowed_at' => 'datetime',
            'rejected_at' => 'datetime',
            'returned_at' => 'datetime',
        ];
    }

    /**
     * Deadline efektif: extended_due_at bila ada, else due_at.
     */
    public function effectiveDueAt(): ?\Illuminate\Support\Carbon
    {
        return $this->extended_due_at ?? $this->due_at;
    }

    public function isExtended(): bool
    {
        return $this->extended_due_at !== null;
    }

    public function extender(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'extended_by');
    }

    public static function statusOptions(): array
    {
        return [
            self::STATUS_PENDING =>
                'Menunggu Persetujuan',

            self::STATUS_APPROVED =>
                'Disetujui / Menunggu Jadwal',

            self::STATUS_BORROWED =>
                'Sedang Dipinjam',

            self::STATUS_PARTIAL =>
                'Dikembalikan Sebagian',

            self::STATUS_RETURNED =>
                'Sudah Dikembalikan',

            self::STATUS_COMPLETED =>
                'Selesai (Bahan Habis Pakai)',

            self::STATUS_REJECTED =>
                'Ditolak',

            self::STATUS_CANCELLED =>
                'Dibatalkan',
        ];
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

    public function borrower(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'borrower_id'
        );
    }

    public function workshop(): BelongsTo
    {
        return $this->belongsTo(
            Workshop::class
        );
    }

    public function assignedToolman(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'assigned_toolman_id'
        );
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'approved_by'
        );
    }

    public function rejecter(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'rejected_by'
        );
    }

    public function returner(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'returned_by'
        );
    }

    public function items(): HasMany
    {
        return $this->hasMany(
            LoanItem::class
        );
    }

    public function toolItems(): HasMany
    {
        return $this->items()
            ->where(
                'is_consumable',
                false
            );
    }

    public function consumableItems(): HasMany
    {
        return $this->items()
            ->where(
                'is_consumable',
                true
            );
    }
}
