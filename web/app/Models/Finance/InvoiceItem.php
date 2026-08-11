<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceItem extends Model
{
    protected $table = 'invoice_items';

    protected $fillable = [
        'invoice_id',
        'fee_item',
        'description',
        'amount',
        'scholarship_adjustment',
        'bursary_adjustment',
        'waiver_adjustment',
        'net_amount',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'scholarship_adjustment' => 'decimal:2',
        'bursary_adjustment' => 'decimal:2',
        'waiver_adjustment' => 'decimal:2',
        'net_amount' => 'decimal:2',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}
