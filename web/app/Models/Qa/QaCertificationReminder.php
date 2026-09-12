<?php

namespace App\Models\Qa;

use App\Models\Staff;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QaCertificationReminder extends Model
{
    protected $table = 'qa_certification_reminders';

    protected $fillable = [
        'staff_id', 'certification_name', 'expiry_date', 'reminder_sent_at',
        'escalation_sent_at', 'is_expired', 'raised_by',
    ];

    protected $casts = [
        'expiry_date' => 'date',
        'reminder_sent_at' => 'datetime',
        'escalation_sent_at' => 'datetime',
        'is_expired' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'staff_id');
    }

    public function raisedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'raised_by');
    }
}
