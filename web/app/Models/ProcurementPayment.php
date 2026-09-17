<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProcurementPayment extends Model
{
    protected $table = 'procurement_payments';

    protected $fillable = [
        'invoice_id',
        'payment_number',
        'supplier_id',
        'amount',
        'retention_amount',
        'released_amount',
        'payment_method',
        'payment_reference',
        'transaction_channel_ref',
        'status',
        'mpesa_stk_request_id',
        'recorded_by',
        'payment_date',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'retention_amount' => 'decimal:2',
        'released_amount' => 'decimal:2',
        'payment_date' => 'date',
        'mpesa_stk_request_id' => 'integer',
        'recorded_by' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(ProcurementInvoice::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function mpesaStkRequest(): BelongsTo
    {
        return $this->belongsTo(MpesaStkRequest::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'recorded_by');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeSuccess($query)
    {
        return $query->where('status', 'success');
    }

    public function scopeMpesa($query)
    {
        return $query->where('payment_method', 'mpesa');
    }

    public function scopeBank($query)
    {
        return $query->where('payment_method', 'bank_transfer');
    }
}
