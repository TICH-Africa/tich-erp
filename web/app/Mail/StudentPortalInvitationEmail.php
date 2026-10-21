<?php

namespace App\Mail;

use App\Mail\Concerns\UsesModuleEnvelope;
use App\Models\Student;
use App\Support\ModuleMail;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class StudentPortalInvitationEmail extends Mailable
{
    use Queueable, SerializesModels, UsesModuleEnvelope;

    public function __construct(
        public Student $student,
        public string $deliveryModule = ModuleMail::NOTIFICATION,
    ) {}

    protected function mailModule(): string
    {
        return $this->deliveryModule;
    }

    public function envelope(): Envelope
    {
        return $this->moduleEnvelope('Activate your TICH student portal account');
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.student-portal-invitation',
            with: [
                'student' => $this->student,
                'activationUrl' => route('portal.activate', ['token' => $this->student->portal_invite_token]),
                'programmeLabel' => (string) $this->student->program?->program_name,
                'campusLabel' => (string) $this->student->campus?->campus_name,
            ],
        );
    }
}
