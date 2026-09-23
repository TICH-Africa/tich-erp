<?php

namespace App\Http\Controllers\Qa;

use App\Http\Controllers\Controller;
use App\Models\LessonPlan;
use App\Services\LessonPlanApprovalService;
use App\Services\StaffPortalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LessonPlanController extends Controller
{
    public function __construct(
        protected LessonPlanApprovalService $lessonPlans,
        protected StaffPortalService $staffPortal,
    ) {}

    public function index(Request $request): View
    {
        abort_unless($this->lessonPlans->userIsQaReviewer($request->user()), 403);

        $filter = $request->string('filter')->toString() ?: 'pending';
        if (! in_array($filter, ['pending', 'acknowledged', 'all'], true)) {
            $filter = 'pending';
        }

        return view('qa.lesson-plans.index', [
            'plans' => $this->lessonPlans->qaInbox($filter === 'all' ? null : $filter),
            'filter' => $filter,
        ]);
    }

    public function show(Request $request, LessonPlan $plan): View
    {
        abort_unless($this->lessonPlans->userIsQaReviewer($request->user()), 403);
        abort_unless((int) $plan->registrar_visible === 1, 404);

        $plan->load([
            'allocation.unit.department',
            'allocation.semester',
            'preparedByStaff',
            'hodStaff',
            'qaAcknowledgedByStaff',
            'approvals.approver',
        ]);

        return view('qa.lesson-plans.show', [
            'plan' => $plan,
            'canAcknowledge' => ! $plan->isQaAcknowledged()
                && in_array($plan->status, ['submitted', 'approved', 'modified', 'rejected'], true),
        ]);
    }

    public function acknowledge(Request $request, LessonPlan $plan): RedirectResponse
    {
        abort_unless($this->lessonPlans->userIsQaReviewer($request->user()), 403);

        $staff = $this->staffPortal->staffForUser($request->user());
        abort_unless($staff, 422, 'A staff profile is required to acknowledge lesson plans.');

        $validated = $request->validate([
            'qa_comments' => ['required', 'string', 'max:5000'],
        ]);

        $this->lessonPlans->acknowledgeByQa($plan, $staff, $validated['qa_comments']);

        return redirect()
            ->route('qa.lesson-plans.show', $plan)
            ->with('status', 'Lesson plan acknowledged. HOD approval continues independently.');
    }
}
