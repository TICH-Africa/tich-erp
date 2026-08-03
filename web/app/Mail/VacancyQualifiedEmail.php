<?php

namespace App\Mail;

use App\Models\RecruitmentApplication;
use App\Models\JobVacancy;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class VacancyQualifiedEmail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public RecruitmentApplication $application,
        public JobVacancy $vacancy
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'You are Qualified - ' . $this->vacancy->job_title,
        );
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
