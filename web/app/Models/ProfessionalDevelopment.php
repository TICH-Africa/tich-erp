<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProfessionalDevelopment extends Model
{
    protected $table = 'professional_development';

    public $timestamps = false;

    protected $fillable = [
        'staff_id',
        'activity_type',
        'activity_name',
        'organizer',
        'start_date',
        'end_date',
        'hours_or_days',
        'cpd_credits_earned',
        'location',
        'is_external',
        'cost',
        'funded_by',
        'certificate_path',
        'is_completed',
        'appraisal_relevance',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'approved_at' => 'datetime',
        'hours_or_days' => 'decimal:2',
        'cpd_credits_earned' => 'decimal:2',
        'cost' => 'decimal:2',
        'is_external' => 'boolean',
        'is_completed' => 'boolean',
    ];

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'approved_by');
    }
}
