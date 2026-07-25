<?php

namespace App\Models;

use App\Support\UiText;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProgramTimetableSession extends Model
{
    protected $table = 'program_timetable_sessions';

    public $timestamps = false;

    protected $fillable = [
        'program_timetable_id', 'unit_id', 'staff_id', 'room_id', 'day_of_week',
        'start_time', 'end_time', 'session_type', 'title', 'venue', 'class_group', 'segment_id',
        'lesson_plan_cleared', 'lesson_plan_id',
    ];

    protected $casts = [
        'start_time' => 'string',
        'end_time' => 'string',
    ];

    public function timetable(): BelongsTo
    {
        return $this->belongsTo(ProgramTimetable::class, 'program_timetable_id');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function segment(): BelongsTo
    {
        return $this->belongsTo(ProgramTimetableSegment::class, 'segment_id');
    }

    public function displayTitle(): string
    {
        if ($this->title) {
            return UiText::normalizeDash($this->title) ?? '';
        }

        if ($this->unit) {
            return $this->unit->displayLabel();
        }

        return ucfirst(str_replace('_', ' ', $this->session_type));
    }

    public function timeLabel(): string
    {
        $start = $this->start_time instanceof \Carbon\Carbon
            ? $this->start_time->format('H:i')
            : substr((string) $this->start_time, 0, 5);
        $end = $this->end_time instanceof \Carbon\Carbon
            ? $this->end_time->format('H:i')
            : substr((string) $this->end_time, 0, 5);

        return UiText::normalizeDash("{$start} - {$end}") ?? "{$start} - {$end}";
    }
}
