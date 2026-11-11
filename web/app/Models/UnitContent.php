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
        'section_title',
        'lesson_number',
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
        'lesson_number' => 'integer',
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

    /**
     * Notes with no lesson number sit below the numbered ones, oldest first.
     */
    public function scopeInLessonOrder($query)
    {
        return $query
            ->orderByRaw('case when lesson_number is null then 1 else 0 end')
            ->orderBy('lesson_number')
            ->orderBy('published_at')
            ->orderBy('id');
    }

    /**
     * Reading order: each section in the order its first lesson appears, numbered lessons first inside it.
     *
     * @param  \Illuminate\Support\Collection<int, self>  $items
     * @return \Illuminate\Support\Collection<int, self>
     */
    public static function arrange($items)
    {
        $sorted = $items->sortBy(fn (self $item): array => [
            $item->lesson_number === null ? 1 : 0,
            (int) $item->lesson_number,
            $item->published_at?->getTimestamp() ?? $item->created_at?->getTimestamp() ?? PHP_INT_MAX,
            (int) $item->id,
        ])->values();

        return $sorted->groupBy(fn (self $item): string => $item->sectionLabel())->flatten()->values();
    }

    public function sectionLabel(): string
    {
        return trim((string) $this->section_title) !== '' ? trim((string) $this->section_title) : 'Lessons';
    }

    public function lessonLabel(): string
    {
        return $this->lesson_number ? 'Lesson '.$this->lesson_number : 'Unnumbered';
    }
}
