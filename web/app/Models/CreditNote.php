<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CreditNote extends Model
{
    protected $table = 'credit_notes';

    protected $fillable = [
        'discrepancy_id',
        'invoice_id',
        'supplier_id',
        'credit_note_number',
        'amount',
        'reason',
        'status',
        'applied_to_invoice_id',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'applied_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function discrepancy(): BelongsTo
    {
        return $this->belongsTo(Discrepancy::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(ProcurementInvoice::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function appliedToInvoice(): BelongsTo
    {
        return $this->belongsTo(ProcurementInvoice::class, 'applied_to_invoice_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'created_by');
    }
}
