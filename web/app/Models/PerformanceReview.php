<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PerformanceReview extends Model
{
    protected $table = 'performance_reviews';

    protected $fillable = [
        'staff_id',
        'review_period',
        'review_type',
        'review_date',
        'score',
        'strengths',
        'areas_for_improvement',
        'goals',
        'overall_comments',
        'reviewed_by',
        'status',
        'created_by',
    ];

    protected $casts = [
        'review_date' => 'date',
        'score' => 'decimal:2',
    ];

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'reviewed_by');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'created_by');
    }
}
