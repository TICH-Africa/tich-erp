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
        'start_time', 'end_time', 'session_type', 'delivery_mode', 'recurrence', 'contact_hours',
        'title', 'venue', 'class_group', 'segment_id',
        'lesson_plan_cleared', 'lesson_plan_id',
        'session_date', 'session_date_to', 'week_no', 'week_to', 'source', 'external_ref',
    ];

    protected $casts = [
        'start_time' => 'string',
        'end_time' => 'string',
        'session_date' => 'date',
        'session_date_to' => 'date',
        'contact_hours' => 'float',
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
        // The unit is what a timetable cell has to say. A typed title is only a fallback for a
        // slot that carries no unit (a paper set name, an orientation session), never a replacement.
        if ($this->unit_id && $this->unit) {
            return $this->unit->displayLabel();
        }

        if ($this->title) {
            return UiText::normalizeDash($this->title) ?? '';
        }

        return self::segmentLabels()[$this->session_type] ?? ucfirst(str_replace('_', ' ', (string) $this->session_type));
    }

    /**
     * @return array<string, string>
     */
    public static function segmentLabels(): array
    {
        return [
            'lesson' => 'Lesson',
            'orientation' => 'Orientation',
            'break' => 'Break',
            'lunch' => 'Lunch',
            'exam' => 'Exam',
            'supplementary' => 'Supplementary exam',
            'special_exam' => 'Special exam',
            'other' => 'Other',
        ];
    }

    /**
     * A rest period or a cohort event such as orientation belongs to everybody at once, so it never
     * needs a unit on the form.
     */
    public static function needsUnit(string $sessionType): bool
    {
        return ! in_array($sessionType, ['break', 'lunch', 'orientation', 'other'], true);
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

    /**
     * @return array<string, string>
     */
    public static function deliveryModes(): array
    {
        return [
            'physical' => 'Physical students',
            'online' => 'Online students',
            'both' => 'Online and physical students',
        ];
    }

    /**
     * Online teaching slots need a meeting link or instructions in the venue field.
     */
    public static function needsOnlineVenue(string $sessionType): bool
    {
        return ! in_array($sessionType, ['break', 'lunch'], true);
    }

    public function isOnline(): bool
    {
        return $this->delivery_mode === 'online';
    }

    public function isSharedRest(): bool
    {
        return in_array($this->session_type, ['break', 'lunch'], true);
    }

    /**
     * A student of either study mode sits this class when it is posted for both cohorts,
     * and rest periods belong to everybody on the timetable.
     */
    public function servesMode(string $mode): bool
    {
        if ($this->isSharedRest()) {
            return true;
        }

        $posted = $this->delivery_mode ?: 'physical';

        return $posted === 'both' || $posted === $mode;
    }

    public function deliveryModeLabel(): string
    {
        return self::deliveryModes()[$this->delivery_mode] ?? 'Physical students';
    }

    /**
     * How often the class repeats. A semester timetable is rarely the same pattern all year -
     * a weekly lecture, a monthly workshop, a class that only runs once in the semester.
     *
     * @return array<string, string>
     */
    public static function recurrences(): array
    {
        return ['weekly' => 'Weekly', 'monthly' => 'Monthly', 'semester' => 'Whole semester'];
    }

    public function recurrenceLabel(): string
    {
        return self::recurrences()[$this->recurrence] ?? 'Weekly';
    }

    /**
     * A weekly or monthly class belongs to one posting of many in the semester, so it needs to say
     * which one - or which run of them when the class only covers part of the semester.
     * Whole-semester classes carry no number.
     */
    public function cycleLabel(): ?string
    {
        if (! $this->week_no || $this->recurrence === 'semester') {
            return null;
        }

        $word = $this->recurrence === 'monthly' ? 'Month' : 'Week';
        $from = (int) $this->week_no;
        $to = (int) $this->week_to;

        return $to > $from ? $word.'s '.$from.'-'.$to : $word.' '.$from;
    }

    /**
     * The days a class actually runs on, which is how an online cohort books its span: week numbers
     * say which posting it belongs to, the dates say when. One day prints as one day.
     */
    public function dateLabel(string $format = 'd M Y'): ?string
    {
        $from = $this->session_date;
        $to = $this->session_date_to;

        if ($from && $to && $to->lt($from)) {
            [$from, $to] = [$to, $from];
        }

        if (! $from) {
            return $to ? 'until '.$to->format($format) : null;
        }

        return $to ? $from->format($format).' - '.$to->format($format) : $from->format($format);
    }
}
