<?php

namespace Tests\Unit\Academics;

use App\Models\ObjectiveQuestion;
use App\Services\AuditService;
use App\Services\ContinuousAssessmentService;
use App\Services\ObjectiveAutoGradingService;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;

class ObjectiveAutoGradingServiceTest extends TestCase
{
    public function test_point_form_answers_earn_proportional_credit_for_each_exact_point(): void
    {
        $question = new ObjectiveQuestion([
            'question_type' => 'short_answer',
            'correct_answer' => "First point\nSecond point\nThird point",
            'points' => 6,
        ]);
        $question->id = 12;

        $service = $this->gradingService();
        $result = $service->gradeSubmission(
            new Collection([$question]),
            [12 => "- first point\n2. Second point\nThird point related"],
            6,
            12,
        );

        $this->assertSame(4.0, $service->pointsAwarded(
            $question,
            "- first point\n2. Second point\nThird point related",
        ));
        $this->assertSame(8.0, $result['score_obtained']);
        $this->assertSame(66.67, $result['percentage_score']);
        $this->assertSame(0, $result['correct_count']);
    }

    public function test_numbered_and_bulleted_exact_points_match_without_duplicate_credit(): void
    {
        $question = new ObjectiveQuestion([
            'id' => 8,
            'question_type' => 'long_answer',
            'correct_answer' => "Point one\nPoint two",
            'points' => 4,
        ]);

        $service = $this->gradingService();

        $this->assertSame(4.0, $service->pointsAwarded(
            $question,
            "* POINT ONE.\n2) Point two.\n- Point one",
        ));
        $this->assertTrue($service->answersMatch($question, "Point one\nPoint two"));
    }

    public function test_related_but_nonmatching_wording_does_not_receive_automatic_credit(): void
    {
        $question = new ObjectiveQuestion([
            'question_type' => 'essay',
            'correct_answer' => 'Public health',
            'points' => 2,
        ]);

        $this->assertSame(
            0.0,
            $this->gradingService()->pointsAwarded($question, 'Community wellbeing'),
        );
    }

    private function gradingService(): ObjectiveAutoGradingService
    {
        return new ObjectiveAutoGradingService(
            $this->createMock(ContinuousAssessmentService::class),
            $this->createMock(AuditService::class),
        );
    }
}
