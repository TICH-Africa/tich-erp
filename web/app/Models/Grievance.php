<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Grievance extends Model
{
    protected $table = 'grievances';

    protected $fillable = [
        'staff_id',
        'assigned_to',
        'grievance_type',
        'description',
        'incident_date',
        'resolution_notes',
        'status',
        'resolved_at',
        'hr_comments',
        'metadata',
    ];

    protected $casts = [
        'incident_date' => 'date',
        'resolved_at' => 'date',
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
}
