<?php

namespace App\Models\Qa;

use App\Models\Staff;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QaTrainingEnrolment extends Model
{
    protected $table = 'qa_training_event_enrolments';

    protected $fillable = [
        'qa_capacity_session_id', 'staff_id', 'status', 'decline_reason',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public const STATUSES = [
        'pending',
        'accepted',
        'declined',
        'deferred',
        'attended',
        'absent',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(QaCapacitySession::class, 'qa_capacity_session_id');
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'staff_id');
    }
}
