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
        'template_id', 'campus_id', 'status', 'generation_notes', 'published_at', 'published_by',
        'created_at', 'updated_at',
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

    public function displayTitle(): string
    {
        if ($this->title) {
            return UiText::normalizeDash($this->title) ?? '';
        }

        return UiText::normalizeDash($this->kindLabel().' - Semester '.$this->teaching_period)
            ?? ($this->kindLabel().' - Semester '.$this->teaching_period);
    }

    protected $casts = [
        'published_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
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
