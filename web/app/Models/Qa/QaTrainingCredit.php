<?php

namespace App\Models\Qa;

use App\Models\Staff;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QaTrainingCredit extends Model
{
    protected $table = 'qa_training_credits';

    protected $fillable = [
        'staff_id', 'credit_type', 'credit_value', 'event_id',
        'awarded_at', 'expires_at',
    ];

    protected $casts = [
        'awarded_at' => 'date',
        'expires_at' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public const CREDIT_TYPES = [
        'CPD',
        'Mandatory',
        'Certification',
    ];

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'staff_id');
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(QaCapacitySession::class, 'event_id');
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function isExpiringSoon(): bool
    {
        if (! $this->expires_at) {
            return false;
        }

        return $this->expires_at->diffInDays(now(), false) <= 30;
    }
}
