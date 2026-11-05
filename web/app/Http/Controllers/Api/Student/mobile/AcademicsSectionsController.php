<?php

namespace App\Http\Controllers\Api\Student\mobile;

use App\Http\Controllers\Api\Student\mobile\Concerns\ResolvesPortalStudent;
use App\Http\Controllers\Api\Student\mobile\Concerns\SerializesStudentPortal;
use App\Http\Controllers\Controller;
use App\Services\StudentPortalDashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class AcademicsSectionsController extends Controller
{
    use ResolvesPortalStudent;
    use SerializesStudentPortal;

    public function __construct(
        protected StudentPortalDashboardService $dashboard,
    ) {}

    public function units(Request $request): JsonResponse
    {
        $student = $this->portalStudent($request);
        $payload = Cache::remember(
            'student.mobile.academics.units.'.$student->id.'.v1',
            20,
            fn () => $this->dashboard->academicsUnitsForMobile($student),
        );

        return response()->json([
            'registered_units' => $this->mapRows($payload['registered_units'] ?? []),
            'curriculum_units' => collect($payload['curriculum_units'] ?? [])->map(function ($item) {
                $unit = is_object($item) ? ($item->unit ?? null) : null;

                return [
                    'id' => $item->id ?? null,
                    'unit_id' => $item->unit_id ?? null,
                    'unit_code' => $unit?->unit_code ?? ($item->unit_code ?? null),
                    'unit_name' => $unit?->unit_name ?? ($item->unit_name ?? null),
                    'semester' => $item->semester ?? null,
                    'credit_hours' => $item->credit_hours ?? $unit?->credit_hours ?? null,
                    'is_core' => (bool) ($item->is_core ?? false),
                ];
            })->values()->all(),
            'registered_unit_count' => (int) ($payload['registered_unit_count'] ?? 0),
            'programme_unit_count' => (int) ($payload['programme_unit_count'] ?? 0),
        ]);
    }

    public function content(Request $request): JsonResponse
    {
        $student = $this->portalStudent($request);
        $payload = Cache::remember(
            'student.mobile.academics.content.'.$student->id.'.v2',
            20,
            fn () => $this->dashboard->academicsContentForMobile($student),
        );

        $items = collect($payload['unit_content'] ?? [])
            ->map(fn ($c) => $this->serializeUnitContentItem($c))
            ->values()
            ->all();

        return response()->json(['unit_content' => $items]);
    }

    public function assessments(Request $request): JsonResponse
    {
        $student = $this->portalStudent($request);
        $payload = Cache::remember(
            'student.mobile.academics.assessments.'.$student->id.'.v1',
            20,
            fn () => $this->dashboard->academicsAssessmentsForMobile($student),
        );

        $assessments = collect($payload['assessments'] ?? []);
        $submissions = collect($payload['my_submissions'] ?? []);

        $items = $assessments->map(function ($assessment) use ($submissions) {
            $submission = $submissions->get($assessment->id);

            return [
                'id' => $assessment->id,
                'title' => $assessment->name ?? $assessment->title,
                'status' => $assessment->status,
                'unit_code' => $assessment->allocation?->unit?->unit_code,
                'unit_name' => $assessment->allocation?->unit?->unit_name,
                'question_count' => (int) ($assessment->questions_count ?? $assessment->questions?->count() ?? 0),
                'submission' => $submission ? [
                    'id' => $submission->id,
                    'score' => $submission->score,
                    'status' => $submission->status,
                    'submitted_at' => optional($submission->submitted_at)?->toIso8601String(),
                ] : null,
            ];
        })->values()->all();

        return response()->json(['assessments' => $items]);
    }
}
