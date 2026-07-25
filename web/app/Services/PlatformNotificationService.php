<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class PlatformNotificationService
{
    public function notifyUser(
        int $userId,
        string $title,
        string $body,
        ?string $entityType = null,
        ?string $entityId = null,
        string $priority = 'normal',
    ): void {
        DB::table('notifications')->insert([
            'user_id' => $userId,
            'title' => $title,
            'body' => $body,
            'channels_sent' => json_encode(['in_app']),
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
    public function notifyUsers(array $userIds, string $title, string $body, ?string $entityType = null, ?string $entityId = null, string $priority = 'normal'): void
    {
        foreach (array_unique(array_filter($userIds)) as $userId) {
            $this->notifyUser((int) $userId, $title, $body, $entityType, $entityId, $priority);
        }
    }
}
