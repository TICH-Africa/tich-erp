<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DonorDisbursement extends Model
{
    public $timestamps = false;

    protected $table = 'donor_disbursements';

    protected $fillable = [
        'disbursement_number',
        'donor_project_id',
        'amount_received',
        'currency_received',
        'exchange_rate',
        'kes_amount',
        'receipt_date',
        'bank_reference',
        'purpose',
        'account_ledger_id',
    ];

    protected $casts = [
        'amount_received' => 'decimal:2',
        'exchange_rate' => 'decimal:4',
        'kes_amount' => 'decimal:2',
        'receipt_date' => 'date',
        'created_at' => 'datetime',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(DonorProject::class, 'donor_project_id');
    }
}
