<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class ObjectiveAssessment extends Model
{
    protected $table = 'objective_assessments';

    public $timestamps = false;

    protected $fillable = [
        'unit_allocation_id',
        'unit_id',
        'semester_id',
        'name',
        'assessment_type',
        'max_score',
        'created_by',
        'status',
        'auto_graded_at',
        'created_at',
        'updated_at',
        'time_limit_minutes',
        'available_from',
        'available_until',
        'show_results_immediately',
        'shuffle_questions',
        'allow_multiple_attempts',
        'max_attempts',
        'student_started_at',
        'student_submitted_at',
        'time_taken_seconds',
    ];

    protected $casts = [
        'max_score' => 'float',
        'auto_graded_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'available_from' => 'datetime',
        'available_until' => 'datetime',
        'show_results_immediately' => 'boolean',
        'shuffle_questions' => 'boolean',
        'allow_multiple_attempts' => 'boolean',
        'student_started_at' => 'datetime',
        'student_submitted_at' => 'datetime',
        'time_taken_seconds' => 'integer',
    ];

    public function allocation(): BelongsTo
    {
        return $this->belongsTo(UnitAllocation::class, 'unit_allocation_id');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(ObjectiveQuestion::class)->orderBy('sort_order');
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(ObjectiveSubmission::class);
    }

    /**
     * The dates a student sits on: their own when the lecturer opened a make-up sitting for them,
     * otherwise the paper's window.
     *
     * @return array{from: \Illuminate\Support\Carbon|null, until: \Illuminate\Support\Carbon|null, makeup: bool}
     */
    public function windowFor(?ObjectiveSubmission $submission): array
    {
        if ($submission && ($submission->open_from || $submission->open_until)) {
            return ['from' => $submission->open_from, 'until' => $submission->open_until, 'makeup' => true];
        }

        return ['from' => $this->available_from, 'until' => $this->available_until, 'makeup' => false];
    }

    /**
     * A make-up sitting keeps its own dates and stays open even after the rest of the class has
     * been marked, so a lecturer can always let a missed student sit.
     */
    public function isOpenFor(?ObjectiveSubmission $submission): bool
    {
        $window = $this->windowFor($submission);
        $now = now();

        if (! $window['makeup'] && $this->status !== 'ready') {
            return false;
        }

        if ($window['from'] && $now->lt($window['from'])) {
            return false;
        }

        return ! ($window['until'] && $now->gt($window['until']));
    }

    public function isMarkedFor(?ObjectiveSubmission $submission): bool
    {
        return $submission !== null
            && ($submission->auto_graded_at !== null || $submission->score_obtained !== null);
    }

    /**
     * The paper as one student sees it. Their seed fixes the order, so reloading or coming back the
     * next day cannot reshuffle it, and it only exists while the lecturer has shuffling on.
     *
     * @return Collection<int, ObjectiveQuestion>
     */
    public function questionsFor(?ObjectiveSubmission $submission): Collection
    {
        $questions = $this->loadMissing('questions')->questions->values();
        $seed = $submission?->shuffle_seed;

        if (! $seed) {
            return $questions;
        }

        return $questions->sortBy(fn ($question) => md5($seed.'|q|'.$question->id))->values();
    }

    /**
     * @return list<string>
     */
    public function optionsFor(ObjectiveQuestion $question, ?ObjectiveSubmission $submission): array
    {
        $options = array_values($question->options ?? []);
        $seed = $submission?->shuffle_seed;

        if (! $seed || $question->question_type !== 'mcq' || count($options) < 2) {
            return $options;
        }

        $weighted = [];

        foreach ($options as $index => $option) {
            $weighted[] = [$option, md5($seed.'|o|'.$question->id.'|'.$index)];
        }

        usort($weighted, fn ($a, $b) => strcmp($a[1], $b[1]));

        return array_column($weighted, 0);
    }

    /**
     * Give a student the shuffled paper the lecturer switched on for this assessment. A re-draw is
     * always honoured, since the lecturer asked for a fresh copy for this sitting.
     */
    public function drawShuffleSeed(ObjectiveSubmission $submission, bool $force = false): void
    {
        if (! $force && (! $this->shuffle_questions || $submission->shuffle_seed)) {
            return;
        }

        $submission->update(['shuffle_seed' => bin2hex(random_bytes(8)), 'updated_at' => now()]);
    }
}
