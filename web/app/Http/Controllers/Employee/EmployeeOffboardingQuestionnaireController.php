<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\OffboardingQuestionnaireInvitation;
use App\Services\EmployeePortalService;
use App\Services\OffboardingQuestionnaireService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmployeeOffboardingQuestionnaireController extends Controller
{
    public function __construct(
        protected EmployeePortalService $employeePortal,
        protected OffboardingQuestionnaireService $questionnaires,
    ) {}

    public function index(Request $request): View
    {
        $staff = $this->staff($request);

        $invitations = OffboardingQuestionnaireInvitation::query()
            ->with('template')
            ->where('staff_id', $staff->id)
            ->orderByDesc('sent_at')
            ->paginate(15);

        return view('employee.offboarding-questionnaires.index', [
            'staff' => $staff,
            'invitations' => $invitations,
        ]);
    }

    public function show(Request $request, OffboardingQuestionnaireInvitation $invitation): View
    {
        $staff = $this->staff($request);
        abort_if($invitation->staff_id !== $staff->id, 403);

        $invitation->load(['template.questions', 'answers']);

        return view('employee.offboarding-questionnaires.show', [
            'staff' => $staff,
            'invitation' => $invitation,
            'prefill' => $invitation->prefill_snapshot ?? [],
            'answers' => $invitation->answers->keyBy('question_id'),
        ]);
    }

    public function submit(Request $request, OffboardingQuestionnaireInvitation $invitation): RedirectResponse
    {
        $staff = $this->staff($request);
        abort_if($invitation->staff_id !== $staff->id, 403);

        $raw = $request->input('answers', []);
        $answers = [];
        foreach ($raw as $questionId => $value) {
            $answers[(int) $questionId] = is_string($value) ? $value : (string) $value;
        }

        try {
            $this->questionnaires->submit($invitation, $staff, $answers);
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['answers' => $e->getMessage()])->withInput();
        }

        return redirect()
            ->route('employee.offboarding-questionnaires.index')
            ->with('success', 'Thank you — your offboarding questionnaire has been submitted to HR.');
    }

    private function staff(Request $request)
    {
        $staff = $request->attributes->get('portal_staff')
            ?? $this->employeePortal->staffForUser($request->user());

        abort_unless($staff, 403);

        return $staff;
    }
}