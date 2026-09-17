<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentAudit extends Model
{
    protected $table = 'payment_audits';

    protected $fillable = [
        'invoice_id',
        'invoice_number',
        'actor',
        'action',
        'result',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(ProcurementInvoice::class);
    }
}
