<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockIssue extends Model
{
    protected $table = 'stock_issues';

    protected $fillable = [
        'inventory_item_id',
        'department_id',
        'requested_by',
        'approved_by',
        'quantity',
        'unit_cost',
        'total_cost',
        'reason',
        'approval_status',
        'status',
        'approved_at',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'approved_at' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'requested_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'approved_by');
    }
}
