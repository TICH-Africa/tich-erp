<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffStatusHistory extends Model
{
    protected $table = 'staff_status_history';

    public $timestamps = false;

    protected $fillable = [
        'staff_id',
        'change_type',
        'previous_status',
        'new_status',
        'reason',
        'metadata',
        'approved_by',
        'approval_reference',
        'effective_date',
        'created_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'effective_date' => 'date',
        'created_at' => 'datetime',
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
