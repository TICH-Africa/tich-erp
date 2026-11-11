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
        $staff = $this->portalStaff($request);

        $payload = Cache::remember(
            'staff.mobile.lesson_plans.index.'.$staff->id,
            25,
            fn () => $this->serializer->lessonPlans($staff),
        );

        return response()->json($payload);
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

    public function show(Request $request, LessonPlan $plan): JsonResponse
    {
        $staff = $this->portalStaff($request);
        abort_unless((int) $plan->prepared_by === (int) $staff->id, 403);

        return response()->json(['plan' => $this->serializePlan($plan)]);
    }

    public function store(Request $request): JsonResponse
    {
        $staff = $this->portalStaff($request);
        $sourceType = $request->input('source_type') === 'upload' ? 'upload' : 'form';

        $validated = $request->validate(array_merge(
            $this->formValidationRules($sourceType),
            $sourceType === 'upload'
                ? ['document' => ['required', 'file', 'max:'.config('tich-lesson-plans.upload.max_kb', 10240), 'mimes:pdf,doc,docx']]
                : [],
        ));

        $planData = $this->lessonPlanDocuments->mapValidatedPlanData($validated, $sourceType);

        if ($sourceType === 'form' && ($planData['form_payload']['session_rows'] ?? []) === []) {
            return response()->json([
                'message' => 'Add at least one row to the lesson session plan table.',
            ], 422);
        }

        if ($sourceType === 'upload') {
            $upload = $this->lessonPlanDocuments->storeUpload($staff, $request->file('document'));
            $planData = array_merge($planData, $upload);
        }

        $allocation = UnitAllocation::query()
            ->where('staff_id', $staff->id)
            ->findOrFail((int) $validated['allocation_id']);

        $plan = $this->teaching->createLessonPlan($staff, $allocation, $planData);

        if ($request->boolean('submit')) {
            try {
                $plan = $this->teaching->submitLessonPlan($plan, $staff);
            } catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) {
                return response()->json([
                    'message' => $exception->getMessage(),
                    'plan' => $this->serializePlan($plan->fresh()),
                ], 422);
            }
        }

        Cache::forget('staff.mobile.lesson_plans.index.'.$staff->id);

        return response()->json([
            'message' => $request->boolean('submit')
                ? 'Lesson plan submitted for approval.'
                : 'Lesson plan saved as draft.',
            'plan' => $this->serializePlan($plan),
            ...$this->serializer->lessonPlans($staff),
        ], 201);
    }

    public function verify(Request $request, LessonPlan $plan): JsonResponse
    {
        $staff = $this->portalStaff($request);
        $plan = $this->teaching->verifyLessonPlan($plan, $staff);

        Cache::forget('staff.mobile.lesson_plans.index.'.$staff->id);

        return response()->json([
            'message' => 'Lesson plan verified. You can now submit it for approval.',
            'plan' => $this->serializePlan($plan),
            ...$this->serializer->lessonPlans($staff),
        ]);
    }

    public function submit(Request $request, LessonPlan $plan): JsonResponse
    {
        $staff = $this->portalStaff($request);
        $plan = $this->teaching->submitLessonPlan($plan, $staff);

        Cache::forget('staff.mobile.lesson_plans.index.'.$staff->id);

        return response()->json([
            'message' => 'Lesson plan submitted for approval.',
            'plan' => $this->serializePlan($plan),
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

    /**
     * @return array<string, mixed>
     */
    private function serializePlan(LessonPlan $plan): array
    {
        $plan->loadMissing(['allocation.unit']);

        return [
            'id' => (int) $plan->id,
            'plan_number' => $plan->plan_number,
            'lesson_title' => $plan->lesson_title,
            'unit_code' => $plan->allocation?->unit?->unit_code,
            'unit_name' => $plan->allocation?->unit?->unit_name,
            'planned_date' => optional($plan->planned_date)?->format('Y-m-d'),
            'week_number' => (int) ($plan->week_number ?? 0),
            'contact_hours' => (int) ($plan->contact_hours ?? 0),
            'status' => $plan->status,
            'source_type' => $plan->source_type ?? 'form',
            'tutor_verified_at' => $plan->tutor_verified_at?->toIso8601String(),
            'is_verified' => $plan->isVerifiedByTutor(),
            'uploaded_file_name' => $plan->uploaded_file_name,
            'can_submit' => $plan->isReadyToSubmit(),
            'hod_comments' => $plan->hod_comments,
        ];
    }
}
