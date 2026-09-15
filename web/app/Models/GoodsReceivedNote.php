<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GoodsReceivedNote extends Model
{
    protected $table = 'goods_received_notes';

    protected $fillable = [
        'grn_number',
        'purchase_order_id',
        'supplier_delivery_note',
        'received_date',
        'received_by',
        'inspection_status',
        'inspection_notes',
        'shortages_or_damages',
        'is_complete',
    ];

    protected $casts = [
        'received_date' => 'date',
        'is_complete' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'received_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(GrnItem::class, 'grn_id');
    }

    public function scopePending($query)
    {
        return $query->where('inspection_status', 'pending');
    }

    public function scopeComplete($query)
    {
        return $query->where('inspection_status', 'complete');
    }
}
