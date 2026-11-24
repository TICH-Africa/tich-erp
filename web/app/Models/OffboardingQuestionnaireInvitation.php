<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OffboardingQuestionnaireInvitation extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_SUBMITTED = 'submitted';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'staff_id',
        'template_id',
        'staff_contract_id',
        'status',
        'prefill_snapshot',
        'sent_by_user_id',
        'sent_at',
        'submitted_at',
        'hr_acknowledged_at',
    ];

    protected function casts(): array
    {
        return [
            'prefill_snapshot' => 'array',
            'sent_at' => 'datetime',
            'submitted_at' => 'datetime',
            'hr_acknowledged_at' => 'datetime',
        ];
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(OffboardingQuestionnaireTemplate::class, 'template_id');
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(StaffContract::class, 'staff_contract_id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by_user_id');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(OffboardingQuestionnaireAnswer::class, 'invitation_id');
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'Awaiting employee',
            self::STATUS_SUBMITTED => 'Submitted',
            self::STATUS_CANCELLED => 'Cancelled',
            default => ucfirst((string) $this->status),
        };
    }

    public function statusBadgeTone(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'warning',
            self::STATUS_SUBMITTED => 'success',
            self::STATUS_CANCELLED => 'danger',
            default => 'secondary',
        };
    }
}