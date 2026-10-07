<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HrCorporateGoal extends Model
{
    protected $table = 'hr_corporate_goals';

    protected $fillable = [
        'cycle_id',
        'parent_id',
        'code',
        'title',
        'description',
        'role_scope',
        'scope_values',
        'weight_hint',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'scope_values' => 'array',
        'is_active' => 'boolean',
        'weight_hint' => 'decimal:2',
    ];

    public function cycle(): BelongsTo
    {
        return $this->belongsTo(HrAppraisalCycle::class, 'cycle_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'created_by');
    }

    public function appliesTo(Staff $staff): bool
    {
        if (! $this->is_active) {
            return false;
        }

        return match ($this->role_scope) {
            'department' => in_array((int) $staff->department_id, array_map('intval', $this->scope_values ?? []), true),
            'job_title' => in_array(
                mb_strtolower(trim((string) $staff->job_title)),
                array_map(fn ($v) => mb_strtolower(trim((string) $v)), $this->scope_values ?? []),
                true
            ),
            default => true,
        };
    }
}
