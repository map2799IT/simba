<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DamageReport extends Model
{
    public const STATUS_REPORTED = 'reported';

    public const STATUS_IN_REPAIR = 'in_repair';

    public const STATUS_REPAIRED = 'repaired';

    public const STATUS_UNREPAIRABLE = 'unrepairable';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'code',
        'item_id',
        'loan_item_id',
        'reported_by',
        'handled_by',
        'completed_by',
        'status',
        'severity',
        'reported_at',
        'started_at',
        'completed_at',
        'condition_before',
        'condition_after',
        'description',
        'diagnosis',
        'action_taken',
        'vendor',
        'repair_cost',
        'notes',
        'resolution_notes',
        'evidence_image',
    ];

    protected function casts(): array
    {
        return [
            'reported_at' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'repair_cost' => 'decimal:2',
        ];
    }

    /**
     * URL bukti gambar (storage). Mengembalikan null bila tidak ada.
     */
    public function getEvidenceImageUrlAttribute(): ?string
    {
        if (empty($this->evidence_image)) {
            return null;
        }

        return asset('storage/' . ltrim($this->evidence_image, '/'));
    }

    public static function statusOptions(): array
    {
        return [
            self::STATUS_REPORTED =>
                'Dilaporkan',

            self::STATUS_IN_REPAIR =>
                'Dalam Perbaikan',

            self::STATUS_REPAIRED =>
                'Selesai Diperbaiki',

            self::STATUS_UNREPAIRABLE =>
                'Tidak Dapat Diperbaiki',

            self::STATUS_CANCELLED =>
                'Dibatalkan',
        ];
    }

    public static function severityOptions(): array
    {
        return [
            'minor_damage' =>
                'Kerusakan Ringan',

            'major_damage' =>
                'Kerusakan Berat',

            'maintenance' =>
                'Memerlukan Perawatan',

            'unfit' =>
                'Tidak Layak Pakai',
        ];
    }

    public static function openStatuses(): array
    {
        return [
            self::STATUS_REPORTED,
            self::STATUS_IN_REPAIR,
        ];
    }

    public function statusLabel(): string
    {
        return self::statusOptions()[$this->status]
            ?? ucfirst($this->status);
    }

    public function severityLabel(): string
    {
        return self::severityOptions()[$this->severity]
            ?? ucfirst($this->severity);
    }

    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            self::STATUS_REPORTED =>
                'text-bg-warning',

            self::STATUS_IN_REPAIR =>
                'text-bg-primary',

            self::STATUS_REPAIRED =>
                'text-bg-success',

            self::STATUS_UNREPAIRABLE =>
                'text-bg-danger',

            self::STATUS_CANCELLED =>
                'text-bg-secondary',

            default =>
                'text-bg-secondary',
        };
    }

    public function severityBadgeClass(): string
    {
        return match ($this->severity) {
            'minor_damage' =>
                'text-bg-warning',

            'major_damage' =>
                'text-bg-danger',

            'maintenance' =>
                'text-bg-info',

            'unfit' =>
                'text-bg-dark',

            default =>
                'text-bg-secondary',
        };
    }

    public function isOpen(): bool
    {
        return in_array(
            $this->status,
            self::openStatuses(),
            true
        );
    }

    public function canStart(): bool
    {
        return $this->status
            === self::STATUS_REPORTED;
    }

    public function canResolve(): bool
    {
        return $this->isOpen();
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(
            Item::class
        );
    }

    public function loanItem(): BelongsTo
    {
        return $this->belongsTo(
            LoanItem::class
        );
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'reported_by'
        );
    }

    public function handler(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'handled_by'
        );
    }

    public function completer(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'completed_by'
        );
    }
}