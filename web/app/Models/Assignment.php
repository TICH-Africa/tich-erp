<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Assignment extends Model
{
    protected $table = 'assignments';

    protected $fillable = [
        'unit_id',
        'unit_allocation_id',
        'semester_id',
        'created_by',
        'title',
        'description',
        'instructions',
        'attachment_path',
        'attachment_filename',
        'mime_type',
        'file_size',
        'max_score',
        'due_date',
        'allow_late_submission',
        'status',
        'published_at',
        'available_from',
        'submission_instructions',
        'display_order',
    ];

    protected $casts = [
        'due_date' => 'datetime',
        'published_at' => 'datetime',
        'available_from' => 'datetime',
        'allow_late_submission' => 'boolean',
        'max_score' => 'decimal:2',
    ];

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function unitAllocation(): BelongsTo
    {
        return $this->belongsTo(UnitAllocation::class);
    }

    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'created_by');
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(AssignmentSubmission::class);
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function scopeAvailable($query)
    {
        $now = now();

        return $query->where('status', 'published')
            ->where(function ($q) use ($now) {
                $q->whereNull('available_from')->orWhere('available_from', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('due_date')->orWhere('due_date', '>=', $now);
            });
    }

    public function isAvailable(): bool
    {
        $now = now();

        if ($this->status !== 'published') {
            return false;
        }

        if ($this->available_from && $now->lt($this->available_from)) {
            return false;
        }

        if ($this->due_date && $now->gt($this->due_date)) {
            return false;
        }

        return true;
    }

    public function isOverdue(): bool
    {
        if (! $this->due_date) {
            return false;
        }

        return now()->gt($this->due_date);
    }
}
