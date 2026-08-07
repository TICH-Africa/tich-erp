<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Feedback extends Model
{
    protected $table = 'feedback';

    protected $fillable = [
        'staff_id',
        'feedback_type',
        'description',
        'status',
        'response',
        'resolved_at',
        'hr_comments',
        'metadata',
    ];

    protected $casts = [
        'resolved_at' => 'date',
        'metadata' => 'array',
    ];

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }
}
