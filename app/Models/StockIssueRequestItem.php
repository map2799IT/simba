<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockIssueRequestItem extends Model
{
    protected $fillable = [
        'stock_issue_request_id',
        'item_id',
        'quantity',
        'asset_ids',
        'notes',
    ];

    protected $casts = [
        'asset_ids' => 'array',
        'quantity' => 'decimal:3',
    ];

    public function request(): BelongsTo
    {
        return $this->belongsTo(StockIssueRequest::class, 'stock_issue_request_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class)->withoutGlobalScopes();
    }
}
