<?php

namespace App\Models\Qa;

use App\Models\Qa\QaTrainingCredit;
use App\Models\Qa\QaTrainingEnrolment;
use App\Models\Staff;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    public function enrolments(): HasMany
    {
        return $this->hasMany(QaTrainingEnrolment::class, 'qa_capacity_session_id');
    }

    public function trainingCredits(): HasMany
    {
        return $this->hasMany(QaTrainingCredit::class, 'event_id');
    }
}
