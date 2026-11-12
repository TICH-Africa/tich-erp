<?php

namespace App\Console\Commands;

use App\Models\ObjectiveAssessment;
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

        $this->info("Marked {$graded} submission(s) across {$closed->count()} closed assessment(s).");

        return self::SUCCESS;
    }
}
