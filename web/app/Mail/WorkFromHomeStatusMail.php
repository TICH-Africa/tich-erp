<?php

namespace App\Mail;

use App\Mail\Concerns\UsesModuleEnvelope;
use App\Models\WorkFromHomeRequest;
use App\Support\ModuleMail;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WorkFromHomeStatusMail extends Mailable
{
    use Queueable, SerializesModels, UsesModuleEnvelope;

    /**
     * @param  'submitted'|'approved'|'rejected'|'returned'  $event
     */
    public function __construct(
        public WorkFromHomeRequest $wfh,
        public string $event,
        public string $recipientName,
    ) {}

    protected function mailModule(): string
    {
        return ModuleMail::HR;
    }

    public function envelope(): Envelope
    {
        $code = $this->wfh->request_code;

        $subject = match ($this->event) {
            'approved' => "Work from home approved ({$code})",
            'rejected' => "Work from home rejected ({$code})",
            'returned' => "Work from home returned for changes ({$code})",
            default => "Work from home request submitted ({$code})",
        };

        return $this->moduleEnvelope($subject);
    }

    public function content(): Content
    {
        $start = $this->wfh->period_start ?? $this->wfh->work_date;
        $end = $this->wfh->period_end ?? $this->wfh->work_date;
        $days = 1;
        if ($start && $end) {
            $days = max(1, $start->diffInDays($end) + 1);
        }

        return new Content(
            view: 'emails.work-from-home-status',
            with: [
                'wfh' => $this->wfh,
                'event' => $this->event,
                'recipientName' => $this->recipientName,
                'workDate' => $this->wfh->work_date,
                'periodStart' => $start,
                'periodEnd' => $end,
                'days' => $days,
                'actionUrl' => \App\Support\MailPublicUrl::to('employee/wfh/'.$this->wfh->id),
            ],
        );
    }
}
