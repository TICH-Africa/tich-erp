<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryTransaction extends Model
{
    protected $table = 'inventory_transactions';

    public const UPDATED_AT = null;

    protected $fillable = [
        'inventory_item_id',
        'transaction_type',
        'quantity',
        'unit_cost',
        'total_cost',
        'reference_table',
        'reference_id',
        'from_location',
        'to_location',
        'department_id',
        'recorded_by',
        'transaction_date',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'transaction_date' => 'date',
        'created_at' => 'datetime',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'recorded_by');
    }
}
