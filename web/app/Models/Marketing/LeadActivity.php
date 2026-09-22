<?php

namespace App\Models\Marketing;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadActivity extends Model
{
    protected $table = 'marketing_lead_activities';

    protected $fillable = [
        'lead_id', 'activity_type', 'scheduled_date', 'scheduled_time',
        'description', 'notes', 'completed', 'completed_date',
        'created_by', 'updated_by',
    ];

    protected $casts = [
        'scheduled_date' => 'date',
        'scheduled_time' => 'datetime',
        'completed_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Marketing\Lead::class, 'lead_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Staff::class, 'created_by');
    }
}
