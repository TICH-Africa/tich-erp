<?php

namespace App\Http\Controllers\Api\Staff\mobile;

use App\Http\Controllers\Api\Staff\mobile\Concerns\ResolvesPortalTeachingStaff;
use App\Http\Controllers\Controller;
use App\Models\LessonPlan;
use App\Models\UnitAllocation;
use App\Services\StaffMobilePortalSerializer;
use App\Services\StaffTeachingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LessonPlansController extends Controller
{
    use ResolvesPortalTeachingStaff;

    public function __construct(
        protected StaffMobilePortalSerializer $serializer,
        protected StaffTeachingService $teaching,
    ) {}

    public function index(Request $request): JsonResponse
    {
        return response()->json($this->serializer->lessonPlans($this->portalStaff($request)));
    }

    public function store(Request $request): JsonResponse
    {
        $staff = $this->portalStaff($request);
        $validated = $request->validate([
            'allocation_id' => ['required', 'integer'],
            'lesson_title' => ['nullable', 'string', 'max:255'],
            'lesson_objectives' => ['required', 'string'],
            'topics_covered' => ['nullable', 'string'],
            'planned_date' => ['required', 'date'],
            'week_number' => ['nullable', 'integer', 'min:1', 'max:20'],
            'contact_hours' => ['nullable', 'integer', 'min:1', 'max:8'],
            'teaching_methods' => ['nullable', 'string'],
            'resources_required' => ['nullable', 'string'],
        ]);

        $allocation = UnitAllocation::query()->findOrFail((int) $validated['allocation_id']);
        $plan = $this->teaching->createLessonPlan($staff, $allocation, $validated);

        return response()->json([
            'message' => 'Lesson plan created.',
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
}
