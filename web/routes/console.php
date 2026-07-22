<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('mail:test {email?}', function (?string $email = null) {
    $email ??= config('mail.from.address');

    if ($issue = \App\Support\MailConfig::smtpPasswordIssue()) {
        $this->error($issue);

        return 1;
    }

    $this->info("Sending test email to {$email} via ".config('mail.default').'...');

    try {
        \Illuminate\Support\Facades\Mail::raw(
            'TICH ERP mail test at '.now()->toDateTimeString(),
            fn ($message) => $message->to($email)->subject('TICH ERP mail test')
        );
        $this->info('Test email sent successfully.');

        return 0;
    } catch (\Throwable $e) {
        $this->error(\App\Support\MailConfig::friendlySmtpError($e->getMessage()));

        return 1;
    }
})->purpose('Send a test email using current MAIL_* settings');

Artisan::command('mail:test-all {email?}', function (?string $email = null) {
    $email ??= config('mail.from.address');

    if ($issue = \App\Support\MailConfig::smtpPasswordIssue()) {
        $this->error($issue);

        return 1;
    }

    $applicant = \App\Models\Applicant::query()->with(['program.department', 'handlingDepartment'])->first();
    $reviewer = \App\Models\User::query()->where('is_active', 1)->whereNotNull('email')->first();

    if (! $applicant) {
        $this->warn('No applicants in database — using a sample applicant for template previews.');
        $applicant = new \App\Models\Applicant([
            'application_number' => 'APP-TEST-001',
            'first_name' => 'Test',
            'surname' => 'Applicant',
            'email' => $email,
            'status' => 'submitted',
            'academic_review_status' => 'pending',
        ]);
        $applicant->id = 0;
    }

    if (! $reviewer) {
        $reviewer = new \App\Models\User([
            'username' => 'reviewer',
            'email' => config('mail.from.address'),
        ]);
        $reviewer->id = 0;
    }

    $mailService = app(\App\Services\ApplicationMailService::class);
    $statusUrl = $mailService->statusCheckUrl($applicant);
    $programName = $applicant->program?->program_name ?? 'Certificate in Community Health Practice';

    $messages = [
        'MFA OTP login code' => new \App\Mail\MfaVerificationMail('123456', 10),
        'Application submission confirmation' => new \App\Mail\ApplicationSubmittedMail($applicant, $programName, $statusUrl),
        'Staff review notification' => new \App\Mail\ApplicationStaffReviewMail(
            $applicant,
            $reviewer,
            $programName,
            $applicant->handlingDepartment?->dept_name ?? $applicant->program?->department?->dept_name ?? 'Health and Social Sciences',
            route('admissions.applications.index')
        ),
        'Application shortlisted' => new \App\Mail\ApplicationShortlistedMail(
            $applicant,
            $programName,
            $statusUrl,
            config('tich-application.admission_fee_notice', 'Contact the finance office regarding the admission fee.')
        ),
        'Application approved' => new \App\Mail\ApplicationStatusUpdatedMail(
            $applicant,
            $programName,
            'Admitted',
            'Approved',
            $statusUrl,
            null,
            'Congratulations — your application has been approved.'
        ),
        'Application rejected' => new \App\Mail\ApplicationStatusUpdatedMail(
            $applicant,
            $programName,
            'Rejected',
            'Rejected',
            $statusUrl,
            'Minimum entry requirements not met.',
            null
        ),
    ];

    $failed = 0;

    foreach ($messages as $label => $mailable) {
        $this->line("→ {$label}...");

        try {
            \Illuminate\Support\Facades\Mail::to($email)->send($mailable);
            $this->info("  Sent: {$label}");
        } catch (\Throwable $e) {
            $failed++;
            $this->error('  Failed: '.\App\Support\MailConfig::friendlySmtpError($e->getMessage()));
        }
    }

    if ($failed > 0) {
        $this->error("{$failed} template(s) failed.");

        return 1;
    }

    $this->info('All '.count($messages).' application email templates sent to '.$email);

    return 0;
})->purpose('Send every automated email template for smoke testing');
