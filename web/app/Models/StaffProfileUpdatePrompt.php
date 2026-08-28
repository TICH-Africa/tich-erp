<?php

namespace App\Models;

use App\Services\EmployeeProfileCompletenessService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffProfileUpdatePrompt extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_FULFILLED = 'fulfilled';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'staff_id',
        'requested_by_user_id',
        'requested_via_module',
        'requested_fields',
        'notes',
        'token',
        'status',
        'emailed_at',
        'fulfilled_at',
        'expires_at',
    ];

    protected $casts = [
        'requested_fields' => 'array',
        'emailed_at' => 'datetime',
        'fulfilled_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_user_id');
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING
            && ($this->expires_at === null || $this->expires_at->isFuture());
    }

    /**
     * @return list<string>
     */
    public function fieldLabels(): array
    {
        $labels = EmployeeProfileCompletenessService::requestableFieldLabels();

        return collect($this->requested_fields ?? [])
            ->map(fn (string $field) => $labels[$field] ?? $field)
            ->values()
            ->all();
    }

    public function portalUrl(): string
    {
        return route('employee.profile.edit', ['prompt' => $this->token]);
    }
}
