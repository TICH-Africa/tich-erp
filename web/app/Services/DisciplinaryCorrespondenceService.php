<?php

namespace App\Services;

use App\Mail\DisciplinaryCorrespondenceMail;
use App\Models\DisciplinaryCase;
use App\Models\DisciplinaryCorrespondence;
use App\Models\Staff;
use App\Models\User;
use App\Support\ModuleMail;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class DisciplinaryCorrespondenceService
{
    public function __construct(
        protected AuditService $auditService,
        protected PlatformNotificationService $notifications,
    ) {}

    public function send(
        DisciplinaryCase $case,
        User $sender,
        string $subject,
        string $body,
    ): DisciplinaryCorrespondence {
        $subject = trim($subject);
        $body = trim($body);

        if ($subject === '' || $body === '') {
            throw new InvalidArgumentException('Subject and message are required.');
        }

        $case->loadMissing('staff.user');
        $recipient = $case->staff;
        if (! $recipient instanceof Staff) {
            throw new InvalidArgumentException('This case has no employee linked.');
        }

        $email = $recipient->resolveErpEmail($recipient->user?->email);
        if ($email === null || $email === '') {
            throw new InvalidArgumentException('The employee has no email address on record.');
        }

        return DB::transaction(function () use ($case, $sender, $subject, $body, $recipient, $email) {
            $correspondence = DisciplinaryCorrespondence::create([
                'disciplinary_case_id' => $case->id,
                'recipient_staff_id' => $recipient->id,
                'sent_by_user_id' => $sender->id,
                'subject' => $subject,
                'body' => $body,
            ]);

            $mailResult = ModuleMail::trySend(
                ModuleMail::HR,
                $email,
                new DisciplinaryCorrespondenceMail($case, $recipient, $subject, $body),
            );

            if (! $mailResult['sent']) {
                throw new InvalidArgumentException($mailResult['error'] ?? 'Could not send the email from HR.');
            }

            $correspondence->update(['emailed_at' => now()]);

            if ($recipient->user_id) {
                $preview = str($body)->limit(280)->toString();
                $this->notifications->notifyUser(
                    (int) $recipient->user_id,
                    $subject,
                    'Human Resources sent you a message regarding disciplinary case '.$case->case_number.'. '.$preview,
                    'disciplinary_correspondence',
                    (string) $correspondence->id,
                    'high',
                    route('employee.dashboard'),
                    false,
                );
                $correspondence->update(['notified_at' => now()]);
            }

            $this->auditService->log(
                'disciplinary.correspondence.sent',
                'disciplinary_correspondences',
                $correspondence->id,
                null,
                [
                    'case_id' => $case->id,
                    'case_number' => $case->case_number,
                    'recipient_staff_id' => $recipient->id,
                    'subject' => $subject,
                ],
                'Disciplinary correspondence emailed to employee',
                'success',
                $sender->id,
            );

            return $correspondence;
        });
    }
}
