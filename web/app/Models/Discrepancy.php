<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Discrepancy extends Model
{
    protected $table = 'discrepancies';

    protected $fillable = [
        'invoice_id',
        'three_way_match_id',
        'supplier_id',
        'discrepancy_type',
        'field_name',
        'po_value',
        'quotation_value',
        'invoice_value',
        'deviation_amount',
        'status',
        'raised_by',
        'resolved_by',
        'resolution_note',
        'resolution_type',
        'credit_note_id',
        'supplier_response_at',
        'resolved_at',
        'escalated_at',
        'supplier_response',
        'is_escalated',
        'is_fraud_suspected',
        'escalation_note',
    ];

    protected $casts = [
        'deviation_amount' => 'decimal:2',
        'resolved_at' => 'datetime',
        'escalated_at' => 'datetime',
        'supplier_response_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(ProcurementInvoice::class);
    }

    public function threeWayMatch(): BelongsTo
    {
        return $this->belongsTo(ThreeWayMatch::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function raisedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'raised_by');
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'resolved_by');
    }

    public function creditNote(): BelongsTo
    {
        return $this->belongsTo(CreditNote::class);
    }
}
