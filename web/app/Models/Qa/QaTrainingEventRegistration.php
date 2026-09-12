<?php

namespace App\Models\Qa;

use App\Models\Staff;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QaTrainingEventRegistration extends Model
{
    protected $table = 'qa_training_event_registrations';

    protected $fillable = [
        'qa_capacity_session_id', 'title', 'description', 'start_at', 'end_at',
        'provider', 'location', 'credit_type', 'credit_value', 'target_audience',
        'max_attendees', 'enrolled_count', 'status', 'created_by',
    ];

    protected $casts = [
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'created_by');
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(QaCapacitySession::class, 'qa_capacity_session_id');
    }
}
