<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseOrder extends Model
{
    public $timestamps = false;

    protected $table = 'purchase_orders';

    protected $fillable = [
        'po_number',
        'supplier_id',
        'requisition_id',
        'issue_date',
        'delivery_date',
        'total_amount',
        'terms',
        'status',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'delivery_date' => 'date',
        'total_amount' => 'decimal:2',
        'created_at' => 'datetime',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }
}
