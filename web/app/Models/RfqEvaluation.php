<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RfqEvaluation extends Model
{
    protected $table = 'rfq_evaluations';

    protected $fillable = [
        'rfq_id',
        'supplier_id',
        'evaluated_by',
        'price_score',
        'technical_score',
        'delivery_score',
        'payment_terms_score',
        'performance_score',
        'total_score',
        'rank',
        'comments',
        'has_conflict_of_interest',
        'conflict_of_interest_details',
        'is_recused',
        'evaluated_at',
    ];

    protected $casts = [
        'price_score' => 'decimal:2',
        'technical_score' => 'decimal:2',
        'delivery_score' => 'decimal:2',
        'payment_terms_score' => 'decimal:2',
        'performance_score' => 'decimal:2',
        'total_score' => 'decimal:2',
        'has_conflict_of_interest' => 'boolean',
        'is_recused' => 'boolean',
        'evaluated_at' => 'datetime',
    ];

    public function rfq(): BelongsTo
    {
        return $this->belongsTo(Rfq::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function evaluator(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'evaluated_by');
    }
}
