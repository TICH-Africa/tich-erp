<?php

use App\Support\ModuleMail;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('mail:test {email?} {--module=notification}', function (?string $email = null) {
    $module = (string) $this->option('module');
    $email ??= ModuleMail::from($module)['address'];

    if ($issue = \App\Support\MailConfig::moduleIssue($module)) {
        $this->error($issue);

        return 1;
    }

    $from = \App\Support\ModuleMail::from($module);
    $this->info("Sending test email to {$email} via ".config('mail.default')." ({$module} / {$from['address']})...");

    try {
        \Illuminate\Support\Facades\Mail::mailer(\App\Support\ModuleMail::mailer($module))->raw(
            'TICH ERP mail test ('.$module.') at '.now()->toDateTimeString(),
            fn ($message) => $message
                ->to($email)
                ->from($from['address'], $from['name'])
                ->subject('TICH ERP mail test ['.$module.']')
        );
        $this->info('Test email sent successfully.');

        return 0;
    } catch (\Throwable $e) {
        $this->error(\App\Support\MailConfig::friendlySmtpError($e->getMessage(), $module));

        return 1;
    }
})->purpose('Send a test email using a module mailbox (hr, academics, finance, otp, notification)');

Artisan::command('mail:test-all {email?}', function (?string $email = null) {
    $email ??= config('mail.from.address');

    foreach (['hr', 'academics', 'finance', 'otp', 'notification'] as $module) {
        if ($issue = \App\Support\MailConfig::moduleIssue($module)) {
            $this->error("[{$module}] {$issue}");

            return 1;
        }
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
            'email' => config('mail.from.address'),
        ]);
        $reviewer->id = 0;
    }

    $mailService = app(\App\Services\ApplicationMailService::class);
    $statusUrl = $mailService->statusCheckUrl($applicant);
    $programName = $applicant->program?->program_name ?? 'Certificate in Community Health Practice';

    $messages = [
        'MFA OTP login code' => [ModuleMail::OTP, new \App\Mail\MfaVerificationMail('123456', 10)],
        'Application submission confirmation' => [ModuleMail::ACADEMICS, new \App\Mail\ApplicationSubmittedMail($applicant, $programName, $statusUrl)],
        'Staff review notification' => [ModuleMail::NOTIFICATION, new \App\Mail\ApplicationStaffReviewMail(
            $applicant,
            $reviewer,
            $programName,
            $applicant->handlingDepartment?->dept_name ?? $applicant->program?->department?->dept_name ?? 'Health and Social Sciences',
            route('admissions.applications.index')
        )],
        'Application shortlisted' => [ModuleMail::ACADEMICS, new \App\Mail\ApplicationShortlistedMail(
            $applicant,
            $programName,
            $statusUrl,
            config('tich-application.admission_fee_notice', 'Contact the finance office regarding the admission fee.')
        )],
        'Application approved' => [ModuleMail::ACADEMICS, new \App\Mail\ApplicationStatusUpdatedMail(
            $applicant,
            $programName,
            'Admitted',
            'Approved',
            $statusUrl,
            null,
            'Congratulations — your application has been approved.',
            url('/portal/activate/sample-token'),
        )],
        'Application rejected' => [ModuleMail::ACADEMICS, new \App\Mail\ApplicationStatusUpdatedMail(
            $applicant,
            $programName,
            'Rejected',
            'Rejected',
            $statusUrl,
            'Minimum entry requirements not met.',
            null
        )],
    ];

    $failed = 0;

    foreach ($messages as $label => [$module, $mailable]) {
        $this->line("→ {$label} ({$module})...");

        try {
            \App\Support\ModuleMail::send($module, $email, $mailable);
            $this->info("  Sent: {$label}");
        } catch (\Throwable $e) {
            $failed++;
            $this->error('  Failed: '.\App\Support\MailConfig::friendlySmtpError($e->getMessage(), $module));
        }
    }

    if ($failed > 0) {
        $this->error("{$failed} template(s) failed.");

        return 1;
    }

    $this->info('All '.count($messages).' application email templates sent to '.$email);

    return 0;
})->purpose('Send every automated email template for smoke testing');

Artisan::command('sis:backfill-admitted', function () {
    $service = app(\App\Services\StudentEnrollmentService::class);
    $count = 0;

    \App\Models\Applicant::query()
        ->where('status', 'admitted')
        ->whereDoesntHave('student')
        ->orderBy('id')
        ->each(function (\App\Models\Applicant $applicant) use ($service, &$count) {
            $student = $service->enrollFromAdmittedApplicant($applicant);
            $count++;
            $this->line("Enrolled: {$applicant->application_number} → {$student->registration_number}");
        });

    $this->info("Created {$count} student record(s) from admitted applications.");

    return 0;
})->purpose('Create student records and portal invites for already-admitted applicants');

Schedule::command('finance:mpesa-reconcile-pending')->everyMinute();
