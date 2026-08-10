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

class VacancyQualifiedEmail extends Mailable
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
        return $this->moduleEnvelope('You are Qualified - '.$this->vacancy->job_title);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.vacancy-qualified',
            with: [
                'application' => $this->application,
                'vacancy' => $this->vacancy,
            ],
        );
    }
}
