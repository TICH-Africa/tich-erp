<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClearanceChecklistItem extends Model
{
    protected $table = 'clearance_checklist_items';

    protected $fillable = [
        'offboarding_request_id',
        'department',
        'item',
        'is_completed',
        'remarks',
        'completed_by',
        'completed_at',
    ];

    protected $casts = [
        'is_completed' => 'boolean',
        'completed_at' => 'datetime',
    ];

    public function offboardingRequest(): BelongsTo
    {
        return $this->belongsTo(OffboardingRequest::class);
    }

    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'completed_by');
    }
}
