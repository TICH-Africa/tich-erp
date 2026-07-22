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
