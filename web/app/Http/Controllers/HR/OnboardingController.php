<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\Staff;
use App\Models\StaffOnboarding;
use App\Services\AuditService;
use App\Services\StaffLifecycleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OnboardingController extends Controller
{
    public function __construct(
        protected StaffLifecycleService $lifecycleService,
        protected AuditService $auditService,
    ) {}

    public function index(Request $request)
    {
        $query = StaffOnboarding::query()
            ->with(['staff', 'staff.department', 'staff.campus', 'applicant'])
            ->when($request->status, fn ($q, $status) => $q->where('status', $status))
            ->when($request->current_step, fn ($q, $step) => $q->where('current_step', $step))
            ->orderByDesc('created_at');

        $perPage = (int) ($request->per_page ?? 25);
        $onboardings = $query->paginate($perPage)->appends($request->query());

        return response()->json([
            'data' => $onboardings->items(),
            'meta' => [
                'total' => $onboardings->total(),
                'per_page' => $onboardings->perPage(),
                'current_page' => $onboardings->currentPage(),
                'last_page' => $onboardings->lastPage(),
            ],
        ]);
    }

    public function show(int $id)
    {
        $onboarding = StaffOnboarding::with([
            'staff',
            'staff.department',
            'staff.campus',
            'staff.lineManager',
            'staff.documents',
            'staff.nextOfKin',
            'staff.allowances',
            'applicant',
        ])->findOrFail($id);

        return response()->json(['data' => $onboarding]);
    }

    public function updateStep(Request $request, int $id)
    {
        $validated = $request->validate([
            'step' => 'required|string|in:biodata,employment_terms,banking,documents,contract,orientation,statutory,ess_account,completed',
            'data' => 'nullable|array',
            'rejection_reason' => 'nullable|string|max:2000',
        ]);

        $onboarding = StaffOnboarding::findOrFail($id);

        if ($onboarding->status === 'completed') {
            return response()->json(['message' => 'Onboarding already completed'], 422);
        }

        $updateData = ['current_step' => $validated['step']];
        if (isset($validated['data'])) {
            $updateData = array_merge($updateData, $validated['data']);
        }
        if (isset($validated['rejection_reason'])) {
            $updateData['rejection_reason'] = $validated['rejection_reason'];
        }

        $updated = $this->lifecycleService->updateOnboardingStep(
            $onboarding->staff_id,
            $validated['step'],
            $updateData,
            $request->user()->id
        );

        return response()->json(['data' => $updated]);
    }

    public function approve(Request $request, int $id)
    {
        $validated = $request->validate([
            'approved_changes' => 'nullable|array',
        ]);

        $onboarding = StaffOnboarding::findOrFail($id);

        if ($onboarding->status !== 'pending_hr_review') {
            return response()->json(['message' => 'Onboarding is not pending HR review'], 422);
        }

        DB::transaction(function () use ($onboarding, $validated, $request) {
            $onboarding->update([
                'status' => 'approved',
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
                'is_biodata_locked' => 1,
                'locked_by' => $request->user()->id,
                'locked_at' => now(),
            ]);

            $onboarding->staff->update(['is_profile_locked' => 1]);

            $this->lifecycleService->lockProfile($onboarding->staff_id, $request->user()->id);

            if (! empty($validated['approved_changes'])) {
                $this->lifecycleService->approveProfileChange($onboarding->staff_id, $validated['approved_changes'], $request->user()->id);
            }

            $this->auditService->log(
                'staff.onboarding.approved',
                'staff_onboarding',
                $onboarding->id,
                ['status' => 'pending_hr_review'],
                ['status' => 'approved'],
                'Onboarding approved by HR',
                'success',
                $request->user()->id,
                $request
            );
        });

        return response()->json(['data' => $onboarding->fresh()]);
    }

    public function reject(Request $request, int $id)
    {
        $validated = $request->validate([
            'rejection_reason' => 'required|string|max:2000',
        ]);

        $onboarding = StaffOnboarding::findOrFail($id);

        if ($onboarding->status !== 'pending_hr_review') {
            return response()->json(['message' => 'Onboarding is not pending HR review'], 422);
        }

        DB::transaction(function () use ($onboarding, $validated, $request) {
            $onboarding->update([
                'status' => 'rejected',
                'rejection_reason' => $validated['rejection_reason'],
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
            ]);

            $this->auditService->log(
                'staff.onboarding.rejected',
                'staff_onboarding',
                $onboarding->id,
                ['status' => 'pending_hr_review'],
                ['status' => 'rejected', 'rejection_reason' => $validated['rejection_reason']],
                'Onboarding rejected',
                'success',
                $request->user()->id,
                $request
            );
        });

        return response()->json(['data' => $onboarding->fresh()]);
    }

    public function complete(Request $request, int $id)
    {
        $onboarding = StaffOnboarding::findOrFail($id);

        if ($onboarding->status === 'completed') {
            return response()->json(['message' => 'Onboarding already completed'], 422);
        }

        $completed = $this->lifecycleService->completeOnboarding($onboarding->staff_id, $request->user()->id);

        return response()->json(['data' => $completed]);
    }
}
