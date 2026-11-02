<?php

namespace App\Http\Controllers\Api\Staff\mobile;

use App\Http\Controllers\Api\Staff\mobile\Concerns\ResolvesPortalTeachingStaff;
use App\Http\Controllers\Controller;
use App\Models\LessonPlan;
use App\Models\UnitAllocation;
use App\Services\LessonPlanContextService;
use App\Services\LessonPlanDocumentService;
use App\Services\StaffMobilePortalSerializer;
use App\Services\StaffTeachingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class LessonPlansController extends Controller
{
    use ResolvesPortalTeachingStaff;

    public function __construct(
        protected StaffMobilePortalSerializer $serializer,
        protected StaffTeachingService $teaching,
        protected LessonPlanDocumentService $lessonPlanDocuments,
        protected LessonPlanContextService $lessonPlanContext,
    ) {}

    public function index(Request $request): JsonResponse
    {
        return response()->json($this->serializer->lessonPlans($this->portalStaff($request)));
    }

    public function context(Request $request): JsonResponse
    {
        $staff = $this->portalStaff($request);
        $request->validate([
            'allocation_id' => ['required', 'integer'],
            'planned_date' => ['nullable', 'date'],
        ]);

        $allocationId = $request->integer('allocation_id');
        $plannedDate = $request->string('planned_date')->toString() ?: '';
        $cacheKey = 'staff.mobile.lesson_plan_context.'.$staff->id.'.'.$allocationId.'.'.md5($plannedDate);

        $defaults = Cache::remember($cacheKey, 45, function () use ($staff, $allocationId, $plannedDate) {
            $allocation = UnitAllocation::query()
                ->where('staff_id', $staff->id)
                ->findOrFail($allocationId);

            return $this->lessonPlanContext->defaultsForAllocation(
                $staff,
                $allocation,
                $plannedDate !== '' ? $plannedDate : null,
            );
        });

        return response()->json(['defaults' => $defaults]);
    }

    public function store(Request $request): JsonResponse
    {
        $staff = $this->portalStaff($request);
        $sourceType = $request->input('source_type') === 'upload' ? 'upload' : 'form';

        $validated = $request->validate($this->formValidationRules($sourceType));

        $planData = $this->lessonPlanDocuments->mapValidatedPlanData($validated, $sourceType);

        if ($sourceType === 'form' && ($planData['form_payload']['session_rows'] ?? []) === []) {
            return response()->json([
                'message' => 'Add at least one row to the lesson session plan table.',
            ], 422);
        }

        $allocation = UnitAllocation::query()
            ->where('staff_id', $staff->id)
            ->findOrFail((int) $validated['allocation_id']);

        $plan = $this->teaching->createLessonPlan($staff, $allocation, $planData);

        return response()->json([
            'message' => 'Lesson plan saved as draft.',
            'plan' => [
                'id' => $plan->id,
                'plan_number' => $plan->plan_number,
                'status' => $plan->status,
            ],
            ...$this->serializer->lessonPlans($staff),
        ], 201);
    }

    public function submit(Request $request, LessonPlan $plan): JsonResponse
    {
        $staff = $this->portalStaff($request);
        $plan = $this->teaching->submitLessonPlan($plan, $staff);

        return response()->json([
            'message' => 'Lesson plan submitted for approval.',
            'plan' => [
                'id' => $plan->id,
                'status' => $plan->status,
            ],
            ...$this->serializer->lessonPlans($staff),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function formValidationRules(string $sourceType): array
    {
        $rules = [
            'allocation_id' => ['required', 'integer'],
            'source_type' => ['nullable', 'string', 'in:form,upload'],
            'lesson_title' => ['required', 'string', 'max:255'],
            'planned_date' => ['required', 'date'],
            'week_number' => ['nullable', 'integer', 'min:1'],
            'contact_hours' => ['required', 'integer', 'min:1'],
            'topics_covered' => ['nullable', 'string'],
            'competencies_targeted' => ['nullable', 'string'],
            'teaching_methods' => ['nullable', 'string', 'max:500'],
            'resources_required' => ['nullable', 'string', 'max:500'],
        ];

        if ($sourceType === 'form') {
            $rules['lesson_objectives'] = ['required', 'string'];
            $rules['competencies_targeted'] = ['required', 'string'];
            $rules['resources_required'] = ['required', 'string'];
            foreach (array_keys(config('tich-lesson-plans.form_fields', [])) as $field) {
                if (in_array($field, ['assignment', 'references'], true)) {
                    $rules[$field] = ['nullable', 'string'];
                } else {
                    $rules[$field] = ['required', 'string'];
                }
            }
            $rules['session_rows'] = ['required', 'array'];
            foreach (array_keys(config('tich-lesson-plans.session_row_columns', [])) as $column) {
                $rules['session_rows.*.'.$column] = ['nullable', 'string', 'max:5000'];
            }
        } else {
            $rules['lesson_objectives'] = ['nullable', 'string'];
        }

        return $rules;
    }
}
