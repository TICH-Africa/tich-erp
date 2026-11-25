<?php

namespace Tests\Unit\Academics;

use App\Models\ObjectiveAssessment;
use App\Models\ObjectiveSubmission;
use App\Services\StudentObjectiveAssessmentProgressService;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class StudentObjectiveAssessmentProgressServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_remaining_time_uses_the_persisted_start_time(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-30 12:00:00'));

        $assessment = new ObjectiveAssessment(['time_limit_minutes' => 5]);
        $submission = new ObjectiveSubmission([
            'student_started_at' => now()->subSeconds(75),
        ]);

        $this->assertSame(
            225,
            app(StudentObjectiveAssessmentProgressService::class)
                ->remainingSeconds($assessment, $submission),
        );
    }

    public function test_remaining_time_does_not_go_below_zero(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-30 12:00:00'));

        $assessment = new ObjectiveAssessment(['time_limit_minutes' => 5]);
        $submission = new ObjectiveSubmission([
            'student_started_at' => now()->subMinutes(6),
        ]);

        $this->assertSame(
            0,
            app(StudentObjectiveAssessmentProgressService::class)
                ->remainingSeconds($assessment, $submission),
        );
    }
}
