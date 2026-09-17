<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ThreeWayMatch extends Model
{
    protected $table = 'three_way_matches';

    protected $fillable = [
        'invoice_id',
        'purchase_order_id',
        'rfq_quotation_id',
        'status',
        'quantity_po',
        'quantity_quotation',
        'quantity_invoiced',
        'unit_price_po',
        'unit_price_quotation',
        'unit_price_invoiced',
        'quantity_tolerance_percent',
        'price_match',
        'description_match',
        'quantity_match',
        'arithmetic_match',
        'delivery_match',
        'total_calculated',
        'total_invoiced',
        'deviation_amount',
        'discrepancy_count',
        'matched_by',
        'matched_at',
        'matching_certificate',
        'notes',
    ];

    protected $casts = [
        'quantity_po' => 'decimal:2',
        'quantity_quotation' => 'decimal:2',
        'quantity_invoiced' => 'decimal:2',
        'unit_price_po' => 'decimal:2',
        'unit_price_quotation' => 'decimal:2',
        'unit_price_invoiced' => 'decimal:2',
        'quantity_tolerance_percent' => 'decimal:2',
        'price_match' => 'boolean',
        'description_match' => 'boolean',
        'quantity_match' => 'boolean',
        'arithmetic_match' => 'boolean',
        'delivery_match' => 'boolean',
        'total_calculated' => 'decimal:2',
        'total_invoiced' => 'decimal:2',
        'deviation_amount' => 'decimal:2',
        'discrepancy_count' => 'integer',
        'matched_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(ProcurementInvoice::class);
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function rfqQuotation(): BelongsTo
    {
        return $this->belongsTo(RfqQuotation::class, 'rfq_quotation_id');
    }

    public function matchedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'matched_by');
    }

    public function discrepancies()
    {
        return $this->hasMany(Discrepancy::class, 'three_way_match_id');
    }
}
