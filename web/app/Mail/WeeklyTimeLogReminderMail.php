<?php

namespace App\Mail;

use App\Mail\Concerns\UsesModuleEnvelope;
use App\Models\Staff;
use App\Support\ModuleMail;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WeeklyTimeLogReminderMail extends Mailable
{
    use Queueable, SerializesModels, UsesModuleEnvelope;

    /**
     * @param  list<array{week_number: int, week_ref: string, label: string}>  $incompleteWeeks
     */
    public function __construct(
        public Staff $staff,
        public string $monthLabel,
        public int $daysUntilMonthEnd,
        public array $incompleteWeeks,
        public string $portalUrl,
    ) {}

    protected function mailModule(): string
    {
        return ModuleMail::HR;
    }

    public function envelope(): Envelope
    {
        return $this->moduleEnvelope('Reminder: complete your weekly time logs — '.$this->monthLabel);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.weekly-time-log-reminder',
        );
    }
}
