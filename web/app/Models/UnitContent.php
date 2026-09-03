<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UnitContent extends Model
{
    protected $table = 'unit_contents';

    protected $fillable = [
        'unit_id',
        'unit_allocation_id',
        'created_by',
        'title',
        'content_type',
        'content_text',
        'file_path',
        'original_filename',
        'mime_type',
        'file_size',
        'external_url',
        'status',
        'published_at',
        'available_from',
        'available_until',
        'display_order',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'available_from' => 'datetime',
        'available_until' => 'datetime',
        'file_size' => 'integer',
        'display_order' => 'integer',
    ];

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function unitAllocation(): BelongsTo
    {
        return $this->belongsTo(UnitAllocation::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'created_by');
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
                $q->whereNull('available_until')->orWhere('available_until', '>=', $now);
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

        if ($this->available_until && $now->gt($this->available_until)) {
            return false;
        }

        return true;
    }
}
