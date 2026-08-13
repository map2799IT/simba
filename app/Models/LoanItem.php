<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoanItem extends Model
{
    protected $fillable = [
        'loan_id',
        'item_id',
        'item_asset_id',
        'workshop_id',
        'quantity',
        'is_consumable',
        'condition_out',
        'issued_at',
        'stock_movement_id',
        'returned_by',
        'condition_in',
        'return_condition',
        'returned_quantity',
        'returned_at',
        'return_status',
        'return_notes',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
            'is_consumable' => 'boolean',
            'issued_at' => 'datetime',
            'returned_quantity' => 'decimal:3',
            'returned_at' => 'datetime',
        ];
    }

    public function loan(): BelongsTo
    {
        return $this->belongsTo(
            Loan::class
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

    public function itemAsset(): BelongsTo
    {
        $relation =
            $this->belongsTo(
                ItemAsset::class
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

    public function returnedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'returned_by'
        );
    }

    public function stockMovement(): BelongsTo
    {
        return $this->belongsTo(
            ItemStockMovement::class
        );
    }
}
