<?php

namespace App\Models\Qa;

use App\Models\Staff;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QaCapacitySession extends Model
{
    protected $table = 'qa_capacity_sessions';

    protected $fillable = [
        'title', 'description', 'scheduled_at', 'audience', 'location',
        'status', 'created_by',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function createdByStaff(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'created_by');
    }
}
