<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProfitLossSnapshot extends Model
{
    protected $fillable = [
        'label',
        'period_preset',
        'period_from',
        'period_to',
        'view_mode',
        'total_revenue',
        'total_expenses',
        'net_income',
        'payload',
        'saved_by',
    ];

    protected $casts = [
        'period_from' => 'date',
        'period_to' => 'date',
        'total_revenue' => 'decimal:2',
        'total_expenses' => 'decimal:2',
        'net_income' => 'decimal:2',
        'payload' => 'array',
    ];

    public function saver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'saved_by');
    }
}
