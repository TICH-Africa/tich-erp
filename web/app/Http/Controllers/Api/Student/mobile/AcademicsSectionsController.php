<?php

namespace App\Http\Controllers\Api\Student\mobile;

use App\Http\Controllers\Api\Student\mobile\Concerns\ResolvesPortalStudent;
use App\Http\Controllers\Api\Student\mobile\Concerns\SerializesStudentPortal;
use App\Http\Controllers\Controller;
use App\Models\SpecialExamRequest;
use App\Models\SupplementaryExamRequest;
use App\Services\StudentPortalDashboardService;
use App\Support\SchemaProbe;
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
            'student.mobile.academics.units.'.$student->id.'.v2',
            120,
            fn () => $this->dashboard->academicsUnitsForMobile($student),
        );

        return response()->json([
            'registered_units' => $this->serializeRegisteredUnits($payload['registered_units'] ?? []),
            'registered_unit_count' => (int) ($payload['registered_unit_count'] ?? 0),
            'programme_unit_count' => (int) ($payload['programme_unit_count'] ?? 0),
        ]);
    }

    /**
     * @param  iterable<int, mixed>  $rows
     * @return list<array<string, mixed>>
     */
    protected function serializeRegisteredUnits(iterable $rows): array
    {
        return collect($rows)->map(function ($row) {
            if (is_object($row)) {
                return [
                    'id' => $row->id ?? null,
                    'unit_id' => $row->unit_id ?? null,
                    'unit_code' => $row->unit_code ?? null,
                    'unit_name' => $row->unit_name ?? null,
                    'semester_id' => $row->semester_id ?? null,
                    'semester_label' => $row->semester_label ?? null,
                    'semester_number' => $row->semester_number ?? null,
                    'credit_hours' => $row->credit_hours ?? null,
                    'registration_status' => $row->registration_status ?? null,
                    'is_additional' => (bool) ($row->is_additional ?? false),
                ];
            }

            if (is_array($row)) {
                return collect($row)->only([
                    'id',
                    'unit_id',
                    'unit_code',
                    'unit_name',
                    'semester_id',
                    'semester_label',
                    'semester_number',
                    'credit_hours',
                    'registration_status',
                    'is_additional',
                ])->all();
            }

            return [];
        })->filter(fn (array $row) => ($row['unit_id'] ?? null) !== null)->values()->all();
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

    public function examRequests(Request $request): JsonResponse
    {
        $student = $this->portalStudent($request);

        $payload = Cache::remember(
            'student.mobile.academics.exam_requests.'.$student->id.'.v1',
            60,
            function () use ($student) {
                $unitsPayload = $this->dashboard->academicsUnitsForMobile($student);
                $special = [];
                $supplementary = [];

                if (SchemaProbe::hasTable('special_exam_requests')) {
                    $special = SpecialExamRequest::query()
                        ->with(['unit:id,unit_code,unit_name', 'semester:id,semester_label'])
                        ->where('student_id', $student->id)
                        ->orderByDesc('created_at')
                        ->limit(30)
                        ->get()
                        ->map(fn ($r) => [
                            'id' => $r->id,
                            'status' => $r->status,
                            'reason' => $r->reason,
                            'unit_code' => $r->unit?->unit_code,
                            'unit_name' => $r->unit?->unit_name,
                            'semester_label' => $r->semester?->semester_label,
                            'created_at' => optional($r->created_at)?->toIso8601String(),
                        ])
                        ->all();
                }

                if (SchemaProbe::hasTable('supplementary_requests')) {
                    $supplementary = SupplementaryExamRequest::query()
                        ->with(['unit:id,unit_code,unit_name', 'semester:id,semester_label'])
                        ->where('student_id', $student->id)
                        ->orderByDesc('created_at')
                        ->limit(30)
                        ->get()
                        ->map(fn ($r) => [
                            'id' => $r->id,
                            'status' => $r->status,
                            'reason' => $r->reason,
                            'unit_code' => $r->unit?->unit_code,
                            'unit_name' => $r->unit?->unit_name,
                            'semester_label' => $r->semester?->semester_label,
                            'created_at' => optional($r->created_at)?->toIso8601String(),
                        ])
                        ->all();
                }

                return [
                    'registered_units' => $this->serializeRegisteredUnits($unitsPayload['registered_units'] ?? []),
                    'special_exam_requests' => $special,
                    'supplementary_exam_requests' => $supplementary,
                ];
            },
        );

        return response()->json($payload);
    }
}
