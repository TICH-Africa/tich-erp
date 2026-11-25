<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\OffboardingQuestionnaireInvitation;
use App\Models\OffboardingQuestionnaireTemplate;
use App\Models\Staff;
use App\Models\StaffContract;
use App\Services\OffboardingQuestionnaireService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OffboardingQuestionnaireInvitationController extends Controller
{
    public function __construct(
        protected OffboardingQuestionnaireService $questionnaires,
    ) {}

    public function index(): View
    {
        $invitations = OffboardingQuestionnaireInvitation::query()
            ->with(['staff', 'template', 'sender'])
            ->orderByDesc('sent_at')
            ->paginate(25);

        return view('hr.offboarding.questionnaires.invitations.index', [
            'invitations' => $invitations,
        ]);
    }

    public function create(Request $request): View
    {
        $staff = Staff::query()
            ->excludePlatformOperators()
            ->with([
                'department',
                'contracts' => fn ($query) => $query
                    ->active()
                    ->whereNotNull('end_date')
                    ->orderBy('end_date'),
            ])
            ->orderBy('surname')
            ->get();

        $templates = OffboardingQuestionnaireTemplate::query()
            ->where('is_active', true)
            ->orderBy('title')
            ->get();

        $expiringContracts = StaffContract::query()
            ->with(['staff.department'])
            ->active()
            ->whereNotNull('end_date')
            ->where('end_date', '<=', now()->addDays(60))
            ->where('end_date', '>=', now())
            ->orderBy('end_date')
            ->limit(50)
            ->get();

        $preselectedStaffId = $request->integer('staff_id') ?: null;

        return view('hr.offboarding.questionnaires.invitations.create', [
            'staffList' => $staff,
            'templates' => $templates,
            'expiringContracts' => $expiringContracts,
            'preselectedStaffId' => $preselectedStaffId,
            'categoryLabels' => config('tich-payroll.employment_categories', []),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'staff_id' => ['required', 'integer', 'exists:staff,id'],
            'template_id' => [
                'required',
                'integer',
                Rule::exists('offboarding_questionnaire_templates', 'id')->where('is_active', true),
            ],
            'staff_contract_id' => ['nullable', 'integer', 'exists:staff_contracts,id'],
        ]);

        $staff = Staff::query()->findOrFail($validated['staff_id']);

        try {
            $invitation = $this->questionnaires->sendToStaff(
                $staff,
                $request->user(),
                (int) $validated['template_id'],
                $validated['staff_contract_id'] ?? null,
            );
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['send' => $e->getMessage()])->withInput();
        }

        return redirect()
            ->route('hr.offboarding.questionnaires.invitations.show', $invitation)
            ->with('success', 'Questionnaire sent to '.$staff->fullName().'.');
    }

    public function show(OffboardingQuestionnaireInvitation $invitation): View
    {
        $invitation->load([
            'staff.department',
            'template.questions',
            'answers.question',
            'sender.staff',
            'contract',
        ]);

        if ($invitation->status === OffboardingQuestionnaireInvitation::STATUS_SUBMITTED
            && $invitation->hr_acknowledged_at === null) {
            $this->questionnaires->acknowledge($invitation, request()->user());
            $invitation->refresh();
        }

        return view('hr.offboarding.questionnaires.invitations.show', [
            'invitation' => $invitation,
            'prefill' => $invitation->prefill_snapshot ?? [],
            'categoryLabels' => config('tich-payroll.employment_categories', []),
        ]);
    }
}