<?php

namespace App\Services;

use App\Mail\PlatformNotificationMail;
use App\Models\User;
use App\Support\ModuleMail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
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

        $row = [
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
        ];

        if (Schema::hasColumn('notifications', 'action_url')) {
            $row['action_url'] = $actionUrl;
        }

        DB::table('notifications')->insert($row);

        // Email is deferred on HTTP so unreachable SMTP cannot block form submissions.
        // In console/schedule there is no response cycle — send immediately.
        $email = $this->resolveRecipientEmail($userId);
        if (! $email) {
            return;
        }

        $sendMail = function () use ($userId, $email, $title, $body, $priority, $actionUrl) {
            $result = ModuleMail::trySend(
                ModuleMail::NOTIFICATION,
                $email,
                new PlatformNotificationMail($title, $body, $priority, $actionUrl)
            );

            if (! $result['sent'] && $result['error']) {
                Log::warning('Platform notification email failed', [
                    'user_id' => $userId,
                    'email' => $email,
                    'error' => $result['error'],
                ]);
            }
        };

        if (app()->runningInConsole()) {
            $sendMail();

            return;
        }

        dispatch($sendMail)->afterResponse();
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

            if ($user->staff) {
                return $user->staff->resolveErpEmail($user->email);
            }

            $email = is_string($user->email) ? trim($user->email) : '';
            if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return $email;
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
