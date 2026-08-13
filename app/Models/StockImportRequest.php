<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockImportRequest extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSED = 'processed';

    protected $fillable = [
        'type',
        'workshop_id',
        'created_by',
        'status',
        'raw_rows',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'raw_rows' => 'array',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function workshop(): BelongsTo
    {
        return $this->belongsTo(Workshop::class);
    }

    public function typeLabel(): string
    {
        return $this->type === 'receipt' ? 'Barang Masuk' : 'Barang Keluar';
    }
}
