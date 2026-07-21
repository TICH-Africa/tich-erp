<?php

namespace App\Services;

use App\Mail\ApplicationStaffReviewMail;
use App\Mail\ApplicationStatusUpdatedMail;
use App\Mail\ApplicationSubmittedMail;
use App\Models\Applicant;
use App\Models\User;
use App\Support\MailConfig;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
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
        ApplicationSubmittedMail|ApplicationStatusUpdatedMail $mailable,
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
            ['reviewer_user_id' => $reviewer->id, 'reviewer_email' => $reviewer->email]
        );
    }

    /**
     * @param  array<string, mixed>  $auditExtra
     * @return array{sent: bool, error: ?string}
     */
    private function sendMail(
        string $email,
        ApplicationSubmittedMail|ApplicationStatusUpdatedMail|ApplicationStaffReviewMail $mailable,
        string $auditAction,
        string $auditDescription,
        Applicant $applicant,
        ?Request $request = null,
        array $auditExtra = [],
    ): array {
        $configError = MailConfig::smtpPasswordIssue()
            ?? ($this->mailIsConfigured() ? null : 'Mail is not configured. Set MAIL_MAILER, MAIL_HOST, MAIL_USERNAME, and MAIL_PASSWORD in .env.');

        if ($configError) {
            Log::warning($configError, [
                'applicant_id' => $applicant->id,
                'recipient' => $email,
            ]);

            return ['sent' => false, 'error' => $configError];
        }

        try {
            Mail::to($email)->send($mailable);

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

            $error = MailConfig::friendlySmtpError($e->getMessage());

            return [
                'sent' => false,
                'error' => config('app.debug') ? $error : 'Unable to send email at this time.',
            ];
        }
    }

    private function mailIsConfigured(): bool
    {
        if (config('mail.default') === 'log' || config('mail.default') === 'array') {
            return true;
        }

        if (config('mail.default') !== 'smtp') {
            return true;
        }

        return ! empty(config('mail.mailers.smtp.host'))
            && ! empty(config('mail.mailers.smtp.username'))
            && config('mail.mailers.smtp.password') !== null
            && config('mail.mailers.smtp.password') !== '';
    }
}
