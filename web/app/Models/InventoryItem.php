<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryItem extends Model
{
    protected $table = 'inventory_items';

    protected $fillable = [
        'item_code',
        'item_name',
        'category',
        'unit_of_measure',
        'current_stock',
        'minimum_stock',
        'maximum_stock',
        'reorder_level',
        'unit_cost',
        'supplier_id',
        'store_location',
        'is_active',
    ];

    protected $casts = [
        'current_stock' => 'integer',
        'minimum_stock' => 'integer',
        'maximum_stock' => 'integer',
        'reorder_level' => 'integer',
        'unit_cost' => 'decimal:2',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(InventoryTransaction::class)->orderByDesc('transaction_date');
    }

    public function alerts(): HasMany
    {
        return $this->hasMany(StockAlert::class);
    }

    public function stockIssues(): HasMany
    {
        return $this->hasMany(StockIssue::class);
    }

    public function isLowStock(): bool
    {
        return $this->current_stock <= $this->reorder_level;
    }

    public function reorderPointReached(): bool
    {
        return $this->current_stock <= $this->reorder_level;
    }

    public function recommendedOrderQuantity(): int
    {
        $buffer = $this->minimum_stock ?? 0;
        $needed = ($this->reorder_level + $buffer) - $this->current_stock;

        return max($needed, 0);
    }
}
