<?php

namespace App\Console\Commands;

use App\Models\ObjectiveAssessment;
use App\Models\ObjectiveSubmission;
use App\Services\ObjectiveAutoGradingService;
use Illuminate\Console\Command;
use Throwable;

class GradeClosedObjectiveAssessments extends Command
{
    protected $signature = 'grading:close-objective-assessments';

    protected $description = 'Auto-mark objective CATs whose availability window has closed and record the marks';

    public function handle(ObjectiveAutoGradingService $grading): int
    {
        $closed = ObjectiveAssessment::query()
            ->where('status', 'ready')
            ->whereNotNull('available_until')
            ->where('available_until', '<=', now())
            ->orderBy('available_until')
            ->get();

        $graded = 0;

        foreach ($closed as $assessment) {
            try {
                $graded += $grading->closeAndGrade($assessment);
            } catch (Throwable $e) {
                $this->warn("Assessment {$assessment->id} skipped: {$e->getMessage()}");
            }
        }

        // Make-up sittings run on their own dates, so they mark on their own close - and a paper
        // already marked still owes a mark to anyone who answered after it closed.
        $waiting = ObjectiveSubmission::query()
            ->whereNotNull('student_submitted_at')
            ->whereNull('auto_graded_at')
            ->where(function ($query) {
                $query->where(fn ($makeup) => $makeup->whereNotNull('open_until')
                        ->where('open_until', '<=', now()))
                    ->orWhere(fn ($missed) => $missed->whereNull('open_until')
                        ->whereHas('assessment', fn ($a) => $a->where('status', 'graded')));
            })
            ->orderBy('id')
            ->get();

        $marked = 0;

        foreach ($waiting as $submission) {
            try {
                $marked += $grading->gradeLateSubmission($submission) ? 1 : 0;
            } catch (Throwable $e) {
                $this->warn("Submission {$submission->id} skipped: {$e->getMessage()}");
            }
        }

        $this->info("Marked {$graded} submission(s) across {$closed->count()} closed assessment(s), plus {$marked} make-up sitting(s).");

        return self::SUCCESS;
    }
}
