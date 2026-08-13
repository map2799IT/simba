<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AuditLog extends Model
{
    public const EVENT_CREATED = 'created';

    public const EVENT_UPDATED = 'updated';

    public const EVENT_DELETED = 'deleted';

    public const EVENT_RESTORED = 'restored';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'event',
        'auditable_type',
        'auditable_id',
        'auditable_label',
        'route_name',
        'url',
        'method',
        'ip_address',
        'user_agent',
        'old_values',
        'new_values',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
            'created_at' => 'immutable_datetime',
        ];
    }

    public static function eventOptions(): array
    {
        return [
            self::EVENT_CREATED => 'Dibuat',
            self::EVENT_UPDATED => 'Diubah',
            self::EVENT_DELETED => 'Dihapus',
            self::EVENT_RESTORED => 'Dipulihkan',
        ];
    }

    public static function modelOptions(): array
    {
        return [
            Workshop::class => 'Bengkel',
            StorageLocation::class => 'Lokasi Penyimpanan',
            ItemCategory::class => 'Kategori Barang',
            Unit::class => 'Satuan',
            Item::class => 'Barang',
            ItemStockMovement::class => 'Mutasi Stok',
            Loan::class => 'Peminjaman',
            LoanItem::class => 'Barang Peminjaman',
            DamageReport::class => 'Laporan Kerusakan',
        ];
    }

    public function eventLabel(): string
    {
        return self::eventOptions()[$this->event]
            ?? ucfirst($this->event);
    }

    public function eventBadgeClass(): string
    {
        return match ($this->event) {
            self::EVENT_CREATED =>
                'text-bg-success',

            self::EVENT_UPDATED =>
                'text-bg-primary',

            self::EVENT_DELETED =>
                'text-bg-danger',

            self::EVENT_RESTORED =>
                'text-bg-info',

            default =>
                'text-bg-secondary',
        };
    }

    public function modelLabel(): string
    {
        return self::modelOptions()[
            $this->auditable_type
        ] ?? class_basename(
            $this->auditable_type
        );
    }

    public function changedFields(): array
    {
        $oldValues = $this->old_values ?? [];
        $newValues = $this->new_values ?? [];

        $keys = array_unique([
            ...array_keys($oldValues),
            ...array_keys($newValues),
        ]);

        sort($keys);

        $changes = [];

        foreach ($keys as $key) {
            $changes[] = [
                'field' => $key,
                'old' => $oldValues[$key] ?? null,
                'new' => $newValues[$key] ?? null,
                'has_old' => array_key_exists(
                    $key,
                    $oldValues
                ),
                'has_new' => array_key_exists(
                    $key,
                    $newValues
                ),
            ];
        }

        return $changes;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }
}