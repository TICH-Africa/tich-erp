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
