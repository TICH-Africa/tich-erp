<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DataBreachIncident extends Model
{
    protected $table = 'data_breach_incidents';

    protected $fillable = [
        'incident_number',
        'discovered_at',
        'reported_odpc_at',
        'affected_subjects_count',
        'data_categories',
        'description',
        'containment_actions',
        'status',
        'created_by',
    ];

    protected $casts = [
        'discovered_at' => 'datetime',
        'reported_odpc_at' => 'datetime',
        'data_categories' => 'array',
        'affected_subjects_count' => 'integer',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
