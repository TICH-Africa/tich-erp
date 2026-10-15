<?php

namespace App\Http\Controllers\Api\Staff\mobile;

use App\Http\Controllers\Api\Staff\mobile\Concerns\ResolvesPortalTeachingStaff;
use App\Http\Controllers\Controller;
use App\Models\UnitAllocation;
use App\Services\StaffMobilePortalSerializer;
use App\Services\StaffTeachingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GradingController extends Controller
{
    use ResolvesPortalTeachingStaff;

    public function __construct(
        protected StaffMobilePortalSerializer $serializer,
        protected StaffTeachingService $teaching,
    ) {}

    public function index(Request $request): JsonResponse
    {
        return response()->json($this->serializer->grading($this->portalStaff($request)));
    }

    public function storeCat(Request $request): JsonResponse
    {
        $staff = $this->portalStaff($request);
        $validated = $request->validate([
            'allocation_id' => ['required', 'integer'],
            'student_id' => ['required', 'integer'],
            'assessment_name' => ['required', 'string', 'max:200'],
            'assessment_type' => ['nullable', 'string', 'max:50'],
            'max_score' => ['required', 'numeric', 'min:0.01'],
            'score_obtained' => ['required', 'numeric', 'min:0'],
            'weight_in_final' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        $allocation = UnitAllocation::query()->findOrFail((int) $validated['allocation_id']);
        $score = $this->teaching->recordCatScore($staff, $allocation, $validated);

        return response()->json([
            'message' => 'Score recorded.',
            'score' => [
                'id' => $score->id,
                'assessment_name' => $score->assessment_name,
                'score_obtained' => (float) $score->score_obtained,
                'max_score' => (float) $score->max_score,
                'percentage_score' => (float) $score->percentage_score,
            ],
            ...$this->serializer->grading($staff),
        ], 201);
    }
}
