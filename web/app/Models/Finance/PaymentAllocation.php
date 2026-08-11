<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentAllocation extends Model
{
    protected $table = 'payment_allocations';

    public $timestamps = false;

    protected $fillable = [
        'payment_id',
        'invoice_id',
        'allocated_amount',
        'allocated_at',
    ];

    protected $casts = [
        'allocated_amount' => 'decimal:2',
        'allocated_at' => 'datetime',
    ];

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}
