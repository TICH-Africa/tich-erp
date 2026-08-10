<?php

namespace App\Services;

use App\Mail\ApplicationShortlistedMail;
use App\Mail\ApplicationStaffReviewMail;
use App\Mail\ApplicationStatusUpdatedMail;
use App\Mail\ApplicationSubmittedMail;
use App\Models\Applicant;
use App\Models\AuditLog;
use App\Models\Student;
use App\Models\User;
use App\Support\MailConfig;
use App\Support\ModuleMail;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

class ApplicationMailService
{
    public function __construct(
        protected AuditService $auditService,
        protected RBACService $rbacService,
    ) {}

    /**
     * @return array{sent: bool, error: ?string}
     */
    public function sendSubmissionConfirmation(Applicant $applicant, ?Request $request = null): array
    {
        $applicant = $this->prepareApplicant($applicant);

        return $this->deliverToApplicant(
            $applicant,
            new ApplicationSubmittedMail(
                $applicant,
                $applicant->program?->program_name ?? 'Selected programme',
                $this->statusCheckUrl($applicant),
            ),
            'admissions.application.confirmation_sent',
            'Application submission confirmation email sent',
            $request
        );
    }

    /**
     * @return array{sent: int, failed: int, errors: list<string>}
     */
    public function notifyStaffForReview(Applicant $applicant, ?Request $request = null): array
    {
        $applicant = $this->prepareApplicant($applicant);
        $recipients = $this->reviewStaffRecipients($applicant);

        if ($recipients->isEmpty()) {
            Log::warning('No staff recipients found for application review notification', [
                'applicant_id' => $applicant->id,
                'application_number' => $applicant->application_number,
            ]);
        }

        $sent = 0;
        $failed = 0;
        $errors = [];

        foreach ($recipients as $reviewer) {
            $result = $this->deliverToStaff($applicant, $reviewer, $request);

            if ($result['sent']) {
                $sent++;
            } else {
                $failed++;
                if ($result['error']) {
                    $errors[] = $result['error'];
                }
            }
        }

        return compact('sent', 'failed', 'errors');
    }

    /**
     * @return array{sent: bool, error: ?string}
     */
    public function sendShortlistNotification(Applicant $applicant, ?Request $request = null): array
    {
        $applicant = $this->prepareApplicant($applicant);

        return $this->deliverToApplicant(
            $applicant,
            new ApplicationShortlistedMail(
                $applicant,
                $applicant->program?->program_name ?? 'Selected programme',
                $this->statusCheckUrl($applicant),
                config('tich-application.admission_fee_notice'),
            ),
            'admissions.application.shortlist_email_sent',
            'Application shortlist notification email sent',
            $request
        );
    }

    /**
     * @return array{sent: bool, error: ?string}
     */
    public function sendStatusUpdate(Applicant $applicant, ?Request $request = null): array
    {
        $applicant = $this->prepareApplicant($applicant);

        return $this->deliverToApplicant(
            $applicant,
            new ApplicationStatusUpdatedMail(
                $applicant,
                $applicant->program?->program_name ?? 'Selected programme',
                $this->formatStatus($applicant->status),
                $this->formatStatus($applicant->academic_review_status),
                $this->statusCheckUrl($applicant),
                $applicant->rejection_reason,
                $applicant->review_notes,
                $this->portalActivationUrlForApplicant($applicant),
            ),
            'admissions.application.status_email_sent',
            'Application status update email sent',
            $request
        );
    }

    public function statusCheckUrl(Applicant $applicant): string
    {
        return route('apply.status', [
            'application_number' => $applicant->application_number,
            'email' => $applicant->email,
        ]);
    }

    public function formatStatus(?string $value): string
    {
        if (! $value) {
            return 'Unknown';
        }

        return ucwords(str_replace('_', ' ', $value));
    }

    public function portalActivationUrlForApplicant(Applicant $applicant): ?string
    {
        if ($applicant->status !== 'admitted') {
            return null;
        }

        $student = Student::query()->where('application_id', $applicant->id)->first();

        return $student?->portalActivationUrl();
    }

    /**
     * @return array{
     *     is_admitted: bool,
     *     portal_activated: bool,
     *     portal_activated_at: ?\Illuminate\Support\Carbon,
     *     student_registered: bool,
     *     registration_number: ?string,
     *     invite_pending: bool,
     *     invite_expires_at: ?\Illuminate\Support\Carbon,
     *     email_sent: bool,
     *     last_sent_at: ?\Illuminate\Support\Carbon,
     *     last_recipient: ?string,
     *     can_resend: bool
     * }
     */
    public function portalSignupEmailStatus(Applicant $applicant): array
    {
        $student = Student::query()->where('application_id', $applicant->id)->first();
        $portalActivated = $student !== null
            && ($student->portal_activated_at !== null || $student->user_id !== null);

        $lastSent = AuditLog::query()
            ->where('entity_type', 'applicants')
            ->where('entity_id', (string) $applicant->id)
            ->where('status', 'success')
            ->whereIn('action', [
                'admissions.application.status_email_sent',
                'admissions.application.portal_signup_email_sent',
            ])
            ->orderByDesc('created_at')
            ->get()
            ->first(function (AuditLog $log) {
                if ($log->action === 'admissions.application.portal_signup_email_sent') {
                    return true;
                }

                return ($log->new_value['status'] ?? null) === 'admitted';
            });

        $isAdmitted = $applicant->status === 'admitted';

        return [
            'is_admitted' => $isAdmitted,
            'portal_activated' => $portalActivated,
            'portal_activated_at' => $student?->portal_activated_at,
            'student_registered' => $student !== null,
            'registration_number' => $student?->registration_number,
            'invite_pending' => $student?->hasActivePortalInvite() ?? false,
            'invite_expires_at' => $student?->portal_invite_expires_at,
            'email_sent' => $lastSent !== null,
            'last_sent_at' => $lastSent?->created_at,
            'last_recipient' => $lastSent?->new_value['recipient'] ?? null,
            'can_resend' => $isAdmitted && ! $portalActivated,
        ];
    }

    /**
     * @return array{sent: bool, error: ?string}
     */
    public function resendPortalSignupEmail(Applicant $applicant, ?Request $request = null): array
    {
        if ($applicant->status !== 'admitted') {
            return [
                'sent' => false,
                'error' => 'Portal signup email is only available for admitted applications.',
            ];
        }

        $enrollmentService = app(StudentEnrollmentService::class);
        $student = Student::query()->where('application_id', $applicant->id)->first();

        if ($student === null) {
            $student = $enrollmentService->enrollFromAdmittedApplicant(
                $applicant,
                $request?->user()?->id
            );
        } elseif ($student->portal_activated_at !== null || $student->user_id !== null) {
            return [
                'sent' => false,
                'error' => 'Student portal is already activated.',
            ];
        } elseif (! $student->hasActivePortalInvite()) {
            $student = $enrollmentService->refreshPortalInvite($student);
        }

        $applicant = $this->prepareApplicant($applicant);

        return $this->deliverToApplicant(
            $applicant,
            new ApplicationStatusUpdatedMail(
                $applicant,
                $applicant->program?->program_name ?? 'Selected programme',
                $this->formatStatus($applicant->status),
                $this->formatStatus($applicant->academic_review_status),
                $this->statusCheckUrl($applicant),
                $applicant->rejection_reason,
                $applicant->review_notes,
                $student->portalActivationUrl(),
            ),
            'admissions.application.portal_signup_email_sent',
            'Student portal signup email sent',
            $request
        );
    }

    public function ensureHandlingDepartment(Applicant $applicant): Applicant
    {
        if ($applicant->handling_department_id) {
            return $applicant;
        }

        $departmentId = $applicant->program?->department_id;

        if (! $departmentId || ! Schema::hasColumn('applicants', 'handling_department_id')) {
            return $applicant;
        }

        DB::table('applicants')
            ->where('id', $applicant->id)
            ->update(['handling_department_id' => $departmentId]);

        $applicant->handling_department_id = $departmentId;

        return $applicant;
    }

    /**
     * @return Collection<int, User>
     */
    public function reviewStaffRecipients(Applicant $applicant): Collection
    {
        $departmentId = (int) ($applicant->handling_department_id ?: $applicant->program?->department_id ?: 0);

        $recipients = User::query()
            ->where('is_active', 1)
            ->get()
            ->filter(function (User $user) use ($departmentId) {
                if (! $user->hasPermission('admissions.read')) {
                    return false;
                }

                if ($this->rbacService->hasAnyRole($user, ['Super Admin', 'Academic Registrar', 'Admissions Officer', 'CEO'])) {
                    return true;
                }

                if ($departmentId === 0) {
                    return false;
                }

                return in_array($departmentId, $this->rbacService->getUserDepartmentIds($user), true);
            })
            ->unique('email')
            ->values();

        if ($recipients->isNotEmpty()) {
            return $recipients;
        }

        $fallbackEmails = config('tich-application.fallback_review_emails', []);

        return User::query()
            ->where('is_active', 1)
            ->whereIn('email', $fallbackEmails)
            ->get();
    }

    private function prepareApplicant(Applicant $applicant): Applicant
    {
        $applicant->loadMissing(['program.department', 'handlingDepartment']);

        return $this->ensureHandlingDepartment($applicant);
    }

    /**
     * @return array{sent: bool, error: ?string}
     */
    private function deliverToApplicant(
        Applicant $applicant,
        ApplicationSubmittedMail|ApplicationStatusUpdatedMail|ApplicationShortlistedMail $mailable,
        string $auditAction,
        string $auditDescription,
        ?Request $request = null,
    ): array {
        if (empty($applicant->email)) {
            return [
                'sent' => false,
                'error' => 'Applicant email address is missing.',
            ];
        }

        return $this->sendMail(
            $applicant->email,
            $mailable,
            $auditAction,
            $auditDescription,
            $applicant,
            $request
        );
    }

    /**
     * @return array{sent: bool, error: ?string}
     */
    private function deliverToStaff(Applicant $applicant, User $reviewer, ?Request $request = null): array
    {
        if (empty($reviewer->email)) {
            return ['sent' => false, 'error' => null];
        }

        $departmentName = $applicant->handlingDepartment?->dept_name
            ?? $applicant->program?->department?->dept_name
            ?? 'Unassigned';

        return $this->sendMail(
            $reviewer->email,
            new ApplicationStaffReviewMail(
                $applicant,
                $reviewer,
                $applicant->program?->program_name ?? 'Selected programme',
                $departmentName,
                route('admissions.applications.show', $applicant->id),
            ),
            'admissions.application.staff_notified',
            'Staff review notification sent',
            $applicant,
            $request,
            ['reviewer_user_id' => $reviewer->id, 'reviewer_email' => $reviewer->email],
            ModuleMail::NOTIFICATION,
        );
    }

    /**
     * @param  array<string, mixed>  $auditExtra
     * @return array{sent: bool, error: ?string}
     */
    private function sendMail(
        string $email,
        ApplicationSubmittedMail|ApplicationStatusUpdatedMail|ApplicationStaffReviewMail|ApplicationShortlistedMail $mailable,
        string $auditAction,
        string $auditDescription,
        Applicant $applicant,
        ?Request $request = null,
        array $auditExtra = [],
        string $module = ModuleMail::ACADEMICS,
    ): array {
        $configError = MailConfig::moduleIssue($module)
            ?? ($this->mailIsConfigured($module) ? null : ucfirst($module).' mail is not configured. Set MAIL_HOST and MAIL_'.strtoupper($module).'_* in .env.');

        if ($configError) {
            Log::warning($configError, [
                'applicant_id' => $applicant->id,
                'recipient' => $email,
            ]);

            return ['sent' => false, 'error' => $configError];
        }

        try {
            ModuleMail::send($module, $email, $mailable);

            $this->auditService->log(
                $auditAction,
                'applicants',
                $applicant->id,
                null,
                array_merge([
                    'application_number' => $applicant->application_number,
                    'recipient' => $email,
                    'status' => $applicant->status,
                ], $auditExtra),
                $auditDescription,
                'success',
                null,
                $request
            );

            return ['sent' => true, 'error' => null];
        } catch (Throwable $e) {
            Log::error('Application email delivery failed', [
                'applicant_id' => $applicant->id,
                'email' => $email,
                'message' => $e->getMessage(),
            ]);

            $error = MailConfig::friendlySmtpError($e->getMessage(), $module);

            return [
                'sent' => false,
                'error' => config('app.debug') ? $error : 'Unable to send email at this time.',
            ];
        }
    }

    private function mailIsConfigured(string $module = ModuleMail::ACADEMICS): bool
    {
        return ModuleMail::isConfigured($module);
    }
}
