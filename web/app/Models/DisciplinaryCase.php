<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DisciplinaryCase extends Model
{
    protected $table = 'disciplinary_cases';

    protected $fillable = [
        'case_number',
        'staff_id',
        'assigned_to',
        'incident_date',
        'incident_description',
        'investigation_notes',
        'witness_information',
        'hearing_date',
        'committee_members',
        'decision',
        'action_type',
        'action_details',
        'action_start_date',
        'action_end_date',
        'status',
        'hr_comments',
        'metadata',
    ];

    protected $casts = [
        'incident_date' => 'date',
        'hearing_date' => 'date',
        'action_start_date' => 'date',
        'action_end_date' => 'date',
        'metadata' => 'array',
    ];

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'assigned_to');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(DisciplinaryDocument::class);
    }

    public function scopeOpen($query)
    {
        return $query->whereNotIn('status', ['closed']);
    }
}
