<?php

namespace App\Models;

use App\Support\UiText;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProgramTimetable extends Model
{
    protected $table = 'program_timetables';

    public $timestamps = false;

    protected $fillable = [
        'program_id', 'curriculum_version_id', 'teaching_period', 'title', 'timetable_kind',
        'cycle_type', 'cycle_no',
        'template_id', 'campus_id', 'status', 'generation_notes', 'progress_note', 'week_span',
        'published_at', 'published_by',
        'created_at', 'updated_at',
        'source', 'source_file', 'source_name', 'source_uploaded_at', 'source_uploaded_by',
    ];

    /**
     * @return array<string, string>
     */
    public static function timetableKinds(): array
    {
        return [
            'lesson' => 'Lesson timetable',
            'exam' => 'Exam timetable',
            'supplementary' => 'Supplementary & special exam timetable',
        ];
    }

    public function kindLabel(): string
    {
        return self::kindLabels()[$this->timetable_kind] ?? ucfirst(str_replace('_', ' ', (string) $this->timetable_kind));
    }

    /**
     * @return array<string, string>
     */
    public static function kindLabels(): array
    {
        return self::timetableKinds();
    }

    /**
     * @return array<string, string>
     */
    public static function cycleTypes(): array
    {
        return [
            'weekly' => 'Weekly - one week of classes',
            'monthly' => 'Monthly - one month of classes',
            'semester' => 'Semesterly - the whole semester at once',
        ];
    }

    public function cycleLabel(): ?string
    {
        return match ($this->cycle_type) {
            'weekly' => $this->cycle_no ? 'Week '.$this->cycle_no : 'Weekly',
            'monthly' => $this->cycle_no ? 'Month '.$this->cycle_no : 'Monthly',
            'semester' => 'Whole semester',
            default => null,
        };
    }

    public function cycleWord(): string
    {
        return $this->cycle_type === 'monthly' ? 'month' : 'week';
    }

    public function nextCycleNo(): int
    {
        return (int) $this->cycle_no + 1;
    }

    public function displayTitle(): string
    {
        $label = $this->title
            ? (UiText::normalizeDash($this->title) ?? '')
            : (UiText::normalizeDash($this->kindLabel().' - Semester '.$this->teaching_period)
                ?? ($this->kindLabel().' - Semester '.$this->teaching_period));

        if ($this->cycleLabel() && ! str_contains($label, $this->cycleLabel())) {
            $label .= ' · '.$this->cycleLabel();
        }

        return $label;
    }

    protected $casts = [
        'published_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'source_uploaded_at' => 'datetime',
        'week_span' => 'integer',
        'cycle_no' => 'integer',
    ];

    public function program(): BelongsTo
    {
        return $this->belongsTo(AcademicProgram::class, 'program_id');
    }

    public function curriculumVersion(): BelongsTo
    {
        return $this->belongsTo(CurriculumVersion::class, 'curriculum_version_id');
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(ProgramTimetableTemplate::class, 'template_id');
    }

    public function campus(): BelongsTo
    {
        return $this->belongsTo(Campus::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(ProgramTimetableSession::class, 'program_timetable_id')
            ->orderBy('day_of_week')
            ->orderBy('start_time');
    }

    public function isPublished(): bool
    {
        return $this->status === 'published';
    }
}
