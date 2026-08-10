<?php

namespace App\Mail;

use App\Mail\Concerns\UsesModuleEnvelope;
use App\Models\JobVacancy;
use App\Models\RecruitmentApplication;
use App\Support\ModuleMail;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class VacancyApplicationReceived extends Mailable
{
    use Queueable, SerializesModels, UsesModuleEnvelope;

    public function __construct(
        public RecruitmentApplication $application,
        public JobVacancy $vacancy
    ) {}

    protected function mailModule(): string
    {
        return ModuleMail::HR;
    }

    public function envelope(): Envelope
    {
        return $this->moduleEnvelope('Application Received - '.$this->vacancy->job_title);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.vacancy-application-received',
            with: [
                'application' => $this->application,
                'vacancy' => $this->vacancy,
            ],
        );
    }
}
