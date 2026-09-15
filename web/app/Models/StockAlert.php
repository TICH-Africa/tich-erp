<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockAlert extends Model
{
    protected $table = 'stock_alerts';

    protected $fillable = [
        'inventory_item_id',
        'alert_type',
        'current_stock',
        'reorder_level',
        'recommended_quantity',
        'triggered_at',
        'closed_at',
        'channels',
        'sent_to',
        'requisition_id',
        'status',
        'notes',
    ];

    protected $casts = [
        'current_stock' => 'integer',
        'reorder_level' => 'integer',
        'recommended_quantity' => 'integer',
        'triggered_at' => 'datetime',
        'closed_at' => 'datetime',
        'channels' => 'array',
        'sent_to' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    public function requisition(): BelongsTo
    {
        return $this->belongsTo(ProcurementRequisition::class);
    }
}
