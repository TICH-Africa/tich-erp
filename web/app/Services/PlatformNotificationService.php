<?php

namespace App\Services;

use App\Mail\PlatformNotificationMail;
use App\Models\User;
use App\Support\ModuleMail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class PlatformNotificationService
{
    public function notifyUser(
        int $userId,
        string $title,
        string $body,
        ?string $entityType = null,
        ?string $entityId = null,
        string $priority = 'normal',
        ?string $actionUrl = null,
    ): void {
        $channels = ['in_app'];

        $email = $this->resolveRecipientEmail($userId);
        if ($email) {
            $result = ModuleMail::trySend(
                ModuleMail::NOTIFICATION,
                $email,
                new PlatformNotificationMail($title, $body, $priority, $actionUrl)
            );

            if ($result['sent']) {
                $channels[] = 'email';
            } elseif ($result['error']) {
                Log::warning('Platform notification email failed', [
                    'user_id' => $userId,
                    'email' => $email,
                    'error' => $result['error'],
                ]);
            }
        }

        DB::table('notifications')->insert([
            'user_id' => $userId,
            'title' => $title,
            'body' => $body,
            'channels_sent' => json_encode($channels),
            'related_entity_type' => $entityType,
            'related_entity_id' => $entityId,
            'priority' => $priority,
            'is_read' => 0,
            'is_dismissed' => 0,
            'created_at' => now(),
        ]);
    }

    /**
     * @param  list<int>  $userIds
     */
    public function notifyUsers(
        array $userIds,
        string $title,
        string $body,
        ?string $entityType = null,
        ?string $entityId = null,
        string $priority = 'normal',
        ?string $actionUrl = null,
    ): void {
        foreach (array_unique(array_filter($userIds)) as $userId) {
            $this->notifyUser((int) $userId, $title, $body, $entityType, $entityId, $priority, $actionUrl);
        }
    }

    private function resolveRecipientEmail(int $userId): ?string
    {
        try {
            $user = User::query()->with('staff')->find($userId);
            if (! $user) {
                return null;
            }

            $staff = $user->staff;
            $candidates = [
                $staff?->organisation_email,
                $staff?->primary_email,
                $user->email,
            ];

            foreach ($candidates as $candidate) {
                $email = is_string($candidate) ? trim($candidate) : '';
                if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    return $email;
                }
            }
        } catch (Throwable $e) {
            Log::warning('Could not resolve notification recipient email', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }
}
