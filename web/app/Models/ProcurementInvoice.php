<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProcurementInvoice extends Model
{
    protected $table = 'procurement_invoices';

    protected $fillable = [
        'invoice_number',
        'supplier_id',
        'purchase_order_id',
        'rfq_id',
        'requisition_id',
        'invoice_date',
        'due_date',
        'subtotal',
        'tax_amount',
        'total_amount',
        'amount_paid',
        'balance',
        'status',
        'retention_percent',
        'retention_amount',
        'released_amount',
        'payment_certificate',
        'notes',
        'created_by',
        'matched_by',
        'matched_at',
        'paid_at',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'balance' => 'decimal:2',
        'retention_percent' => 'decimal:2',
        'retention_amount' => 'decimal:2',
        'released_amount' => 'decimal:2',
        'matched_at' => 'datetime',
        'paid_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
    }

    public function rfq(): BelongsTo
    {
        return $this->belongsTo(Rfq::class);
    }

    public function requisition(): BelongsTo
    {
        return $this->belongsTo(ProcurementRequisition::class);
    }

    public function match(): BelongsTo
    {
        return $this->belongsTo(ThreeWayMatch::class, 'invoice_id');
    }

    public function payments()
    {
        return $this->hasMany(ProcurementPayment::class, 'invoice_id');
    }

    public function discrepancies()
    {
        return $this->hasMany(Discrepancy::class, 'invoice_id');
    }

    public function creditNotes()
    {
        return $this->hasMany(CreditNote::class, 'invoice_id');
    }

    public function scopeReadyForPayment($query)
    {
        return $query->where('status', 'matched')->where('balance', '>', 0);
    }

    public function scopeWithDiscrepancies($query)
    {
        return $query->whereHas('discrepancies', fn ($q) => $q->where('status', 'open'));
    }

    public function scopePendingMatching($query)
    {
        return $query->where('status', 'pending_matching');
    }
}
