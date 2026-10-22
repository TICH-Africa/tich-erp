<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CurriculumVersionPeriod extends Model
{
    protected $table = 'curriculum_version_periods';

    public $timestamps = false;

    protected $fillable = [
        'curriculum_version_id',
        'semester',
        'block_id',
        'start_date',
        'end_date',
        'learning_start_date',
        'learning_end_date',
        'exam_start_date',
        'exam_end_date',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'learning_start_date' => 'date',
        'learning_end_date' => 'date',
        'exam_start_date' => 'date',
        'exam_end_date' => 'date',
    ];

    public function version(): BelongsTo
    {
        return $this->belongsTo(CurriculumVersion::class, 'curriculum_version_id');
    }

    public function block(): BelongsTo
    {
        return $this->belongsTo(NursingBlock::class, 'block_id');
    }

    /**
     * Set when these dates were borrowed from the semester the intake is actually running, so a
     * screen can say where they came from instead of asking the office to type them again.
     */
    public ?CurriculumVersionPeriod $borrowedFrom = null;

    public function isInherited(): bool
    {
        return $this->borrowedFrom !== null;
    }

    /**
     * The semester the college is in right now: the latest one already started, or the next one
     * scheduled when nothing has begun. Every other year of study runs on this calendar.
     *
     * @param  \Illuminate\Support\Collection<int, self>  $periods
     */
    public static function runningPeriod(\Illuminate\Support\Collection $periods, ?\Illuminate\Support\Carbon $today = null): ?self
    {
        $today = $today ?? now();

        $started = $periods
            ->filter(fn (self $period) => $period->start_date && $period->start_date->lte($today))
            ->sortBy(fn (self $period) => $period->start_date->timestamp)
            ->last();

        if ($started) {
            return $started;
        }

        return $periods
            ->filter(fn (self $period) => $period->start_date)
            ->sortBy(fn (self $period) => $period->start_date->timestamp)
            ->first();
    }

    /**
     * A teaching period with no dates of its own - Year 2, 3 or 4 - runs on the semester that is
     * actually going on, so one calendar covers the whole programme unless a year is given its own.
     */
    public static function borrowFrom(self $source, int $semester, ?int $blockId = null): self
    {
        $period = new static([
            'curriculum_version_id' => $source->curriculum_version_id,
            'semester' => $semester,
            'block_id' => $blockId,
            'start_date' => $source->start_date,
            'end_date' => $source->end_date,
            'learning_start_date' => $source->learning_start_date,
            'learning_end_date' => $source->learning_end_date,
            'exam_start_date' => $source->exam_start_date,
            'exam_end_date' => $source->exam_end_date,
        ]);

        $period->borrowedFrom = $source;

        return $period;
    }

    /**
     * The dates a teaching period runs on, borrowing the running semester's window when the period
     * itself has none.
     */
    public static function resolveFor(int $curriculumVersionId, int $semester, ?int $blockId = null): ?self
    {
        $periods = static::query()
            ->where('curriculum_version_id', $curriculumVersionId)
            ->whereNull('block_id')
            ->orderBy('semester')
            ->get();

        $own = $periods->first(fn (self $period) => (int) $period->semester === $semester);

        if ($own && ($own->start_date || $own->end_date || $own->learning_start_date || $own->exam_start_date)) {
            return $own;
        }

        $running = static::runningPeriod($periods);

        return $running ? static::borrowFrom($running, $semester) : $own;
    }

    public function isActiveOn(\Illuminate\Support\Carbon $date): bool
    {
        if (! $this->start_date || ! $this->end_date) {
            return false;
        }

        return $date->between($this->start_date, $this->end_date);
    }

    public function scheduleLabel(): ?string
    {
        if (! $this->start_date && ! $this->end_date) {
            return null;
        }

        if ($this->start_date && $this->end_date) {
            return $this->start_date->format('d M Y').' - '.$this->end_date->format('d M Y');
        }

        return $this->start_date?->format('d M Y') ?? $this->end_date?->format('d M Y');
    }

    public function effectiveLearningStart(): ?\Illuminate\Support\Carbon
    {
        return $this->learning_start_date ?? $this->start_date;
    }

    public function effectiveExamEnd(): ?\Illuminate\Support\Carbon
    {
        return $this->exam_end_date ?? $this->end_date;
    }
}
