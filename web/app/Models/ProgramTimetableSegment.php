<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProgramTimetableSegment extends Model
{
    protected $table = 'program_timetable_segments';

    public $timestamps = false;

    protected $fillable = [
        'template_id', 'label', 'start_time', 'end_time', 'segment_type', 'sort_order',
    ];

    protected $casts = [
        'start_time' => 'string',
        'end_time' => 'string',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(ProgramTimetableTemplate::class, 'template_id');
    }

    public function isSchedulable(): bool
    {
        return in_array($this->segment_type, ['lesson', 'exam', 'supplementary', 'special_exam'], true);
    }

    public function timeLabel(): string
    {
        $start = $this->start_time instanceof \Carbon\Carbon
            ? $this->start_time->format('H:i')
            : substr((string) $this->start_time, 0, 5);
        $end = $this->end_time instanceof \Carbon\Carbon
            ? $this->end_time->format('H:i')
            : substr((string) $this->end_time, 0, 5);

        return "{$start} – {$end}";
    }
}
