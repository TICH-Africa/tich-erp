<?php

namespace App\Services;

use App\Models\OffboardingQuestionnaireAnswer;
use App\Models\OffboardingQuestionnaireInvitation;
use App\Models\OffboardingQuestionnaireQuestion;
use App\Models\OffboardingQuestionnaireTemplate;
use App\Models\Staff;
use App\Models\StaffContract;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class OffboardingQuestionnaireService
{
    public function __construct(
        protected PlatformNotificationService $notifications,
        protected RBACService $rbac,
        protected AuditService $audit,
    ) {}

    public function resolveTemplateForStaff(Staff $staff): ?OffboardingQuestionnaireTemplate
    {
        $category = \App\Support\EmploymentCategory::normalize(
            strtolower((string) ($staff->employment_category ?? ''))
        );
        if ($category === null || $category === '') {
            return null;
        }

        return OffboardingQuestionnaireTemplate::query()
            ->where('is_active', true)
            ->whereJsonContains('employment_categories', $category)
            ->orderByDesc('updated_at')
            ->first();
    }

    /**
     * @return array<string, mixed>
     */
    public function buildPrefillSnapshot(Staff $staff, ?StaffContract $contract = null): array
    {
        $staff->loadMissing(['department', 'user']);

        $positions = array_values(array_filter([
            $staff->job_title,
            $contract?->job_title,
        ]));

        $departments = array_values(array_unique(array_filter([
            $staff->department?->dept_name,
            $contract?->department?->dept_name,
        ])));

        $departure = $contract?->end_date
            ?? $staff->contract_end_date
            ?? $staff->exit_date;

        return [
            'employee_name' => $staff->fullName(),
            'employee_number' => $staff->employee_number,
            'positions' => $positions,
            'departments' => $departments,
            'date_joined' => $staff->employment_start_date?->format('Y-m-d'),
            'expected_departure_date' => $departure?->format('Y-m-d'),
            'employment_category' => $staff->employment_category,
        ];
    }

    public function sendToStaff(
        Staff $staff,
        User $sender,
        ?int $templateId = null,
        ?int $staffContractId = null,
    ): OffboardingQuestionnaireInvitation {
        $template = $templateId
            ? OffboardingQuestionnaireTemplate::query()->where('is_active', true)->findOrFail($templateId)
            : $this->resolveTemplateForStaff($staff);

        if (! $template) {
            throw new \InvalidArgumentException(
                'No active questionnaire template is assigned to this employee’s category ('.
                ($staff->employment_category ?? 'unknown').').'
            );
        }

        $category = \App\Support\EmploymentCategory::normalize(
            strtolower((string) $staff->employment_category)
        );
        $allowed = array_values(array_filter(array_map(
            fn ($code) => \App\Support\EmploymentCategory::normalize(strtolower((string) $code)),
            $template->employment_categories ?? []
        )));
        if ($category !== null && $category !== '' && $allowed !== [] && ! in_array($category, $allowed, true)) {
            throw new \InvalidArgumentException('Selected template does not apply to this employee category.');
        }

        $pendingExists = OffboardingQuestionnaireInvitation::query()
            ->where('staff_id', $staff->id)
            ->where('template_id', $template->id)
            ->where('status', OffboardingQuestionnaireInvitation::STATUS_PENDING)
            ->exists();

        if ($pendingExists) {
            throw new \InvalidArgumentException('This employee already has a pending offboarding questionnaire for that template.');
        }

        $contract = null;
        if ($staffContractId) {
            $contract = StaffContract::query()
                ->with('department')
                ->where('staff_id', $staff->id)
                ->find($staffContractId);
        }

        return DB::transaction(function () use ($staff, $sender, $template, $contract, $staffContractId) {
            $invitation = OffboardingQuestionnaireInvitation::query()->create([
                'staff_id' => $staff->id,
                'template_id' => $template->id,
                'staff_contract_id' => $staffContractId,
                'status' => OffboardingQuestionnaireInvitation::STATUS_PENDING,
                'prefill_snapshot' => $this->buildPrefillSnapshot($staff, $contract),
                'sent_by_user_id' => $sender->id,
                'sent_at' => now(),
            ]);

            $this->notifyEmployee($staff, $invitation);
            app(HrSidebarNotificationService::class)->broadcastCounts();
            $this->audit->log(
                'hr.offboarding_questionnaire.sent',
                'offboarding_questionnaire_invitation',
                $invitation->id,
                null,
                ['staff_id' => $staff->id, 'template_id' => $template->id],
                null,
                'success',
                $sender->id,
            );

            return $invitation->fresh(['template', 'staff']);
        });
    }

    /**
     * @param  array<int, string>  $answers keyed by question_id
     */
    public function submit(OffboardingQuestionnaireInvitation $invitation, Staff $staff, array $answers): void
    {
        if ($invitation->staff_id !== $staff->id) {
            abort(403);
        }

        if (! $invitation->isPending()) {
            throw new \InvalidArgumentException('This questionnaire has already been submitted or closed.');
        }

        $questions = OffboardingQuestionnaireQuestion::query()
            ->where('template_id', $invitation->template_id)
            ->orderBy('display_order')
            ->get();

        foreach ($questions as $question) {
            if (! $question->is_required) {
                continue;
            }
            $value = trim((string) ($answers[$question->id] ?? ''));
            if ($value === '') {
                throw new \InvalidArgumentException('Please answer all required questions.');
            }
        }

        DB::transaction(function () use ($invitation, $questions, $answers, $staff) {
            foreach ($questions as $question) {
                $value = trim((string) ($answers[$question->id] ?? ''));
                if ($value === '') {
                    continue;
                }

                OffboardingQuestionnaireAnswer::query()->updateOrCreate(
                    [
                        'invitation_id' => $invitation->id,
                        'question_id' => $question->id,
                    ],
                    ['answer_text' => $value],
                );
            }

            $invitation->update([
                'status' => OffboardingQuestionnaireInvitation::STATUS_SUBMITTED,
                'submitted_at' => now(),
            ]);

            $this->notifyHrSubmitted($staff, $invitation->fresh(['template']));
            app(HrSidebarNotificationService::class)->broadcastCounts();
            $this->audit->log(
                'employee.offboarding_questionnaire.submitted',
                'offboarding_questionnaire_invitation',
                $invitation->id,
                null,
                ['staff_id' => $staff->id],
            );
        });
    }

    public function acknowledge(OffboardingQuestionnaireInvitation $invitation, User $user): void
    {
        if ($invitation->status !== OffboardingQuestionnaireInvitation::STATUS_SUBMITTED) {
            return;
        }

        $invitation->update(['hr_acknowledged_at' => now()]);
        $this->audit->log(
            'hr.offboarding_questionnaire.acknowledged',
            'offboarding_questionnaire_invitation',
            $invitation->id,
            null,
            [],
            null,
            'success',
            $user->id,
        );
    }

    private function notifyEmployee(Staff $staff, OffboardingQuestionnaireInvitation $invitation): void
    {
        $userId = (int) ($staff->user_id ?? 0);
        if ($userId <= 0) {
            return;
        }

        $title = 'Offboarding questionnaire';
        $body = 'HR has sent you an exit questionnaire to complete before your departure. '
            .'Your name, role, and dates are pre-filled — please answer the questions and submit.';

        $this->notifications->notifyUser(
            $userId,
            $title,
            $body,
            'offboarding_questionnaire',
            (string) $invitation->id,
            'high',
            route('employee.offboarding-questionnaires.show', $invitation),
            true,
        );
    }

    private function notifyHrSubmitted(Staff $staff, OffboardingQuestionnaireInvitation $invitation): void
    {
        $userIds = $this->rbac->hrNotifierUserIds(includeExecutives: false);
        if ($userIds === []) {
            return;
        }

        $title = 'Offboarding questionnaire submitted';
        $body = sprintf(
            '%s (%s) submitted the offboarding questionnaire "%s".',
            $staff->fullName(),
            $staff->employee_number,
            $invitation->template?->title ?? 'Exit questionnaire',
        );

        $this->notifications->notifyUsers(
            $userIds,
            $title,
            $body,
            'offboarding_questionnaire',
            (string) $invitation->id,
            'normal',
            route('hr.offboarding.questionnaires.invitations.show', $invitation),
        );
    }

    public function pendingHrReviewCount(): int
    {
        return OffboardingQuestionnaireInvitation::query()
            ->where('status', OffboardingQuestionnaireInvitation::STATUS_SUBMITTED)
            ->whereNull('hr_acknowledged_at')
            ->count();
    }
}