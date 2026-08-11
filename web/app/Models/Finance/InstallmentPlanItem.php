<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstallmentPlanItem extends Model
{
    protected $table = 'installment_plan_items';

    protected $fillable = [
        'installment_plan_id',
        'installment_number',
        'amount',
        'due_date',
        'status',
        'paid_amount',
        'paid_at',
    ];

    protected $casts = [
        'due_date' => 'date',
        'paid_at' => 'datetime',
        'amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
    ];

    public function installmentPlan(): BelongsTo
    {
        return $this->belongsTo(InstallmentPlan::class);
    }
}
