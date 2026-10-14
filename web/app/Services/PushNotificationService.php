<?php

namespace App\Services;

use App\Models\DeviceToken;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class PushNotificationService
{
    /**
     * Send a high-priority data+notification push to all active devices for a user.
     * Uses FCM legacy HTTP API when FCM_SERVER_KEY is configured.
     */
    public function sendToUser(int $userId, string $title, string $body, array $data = []): void
    {
        if (! Schema::hasTable('device_tokens')) {
            return;
        }

        $tokens = DeviceToken::query()
            ->where('user_id', $userId)
            ->where('is_active', 1)
            ->pluck('device_token')
            ->filter()
            ->unique()
            ->values();

        if ($tokens->isEmpty()) {
            return;
        }

        $serverKey = (string) config('services.fcm.server_key', env('FCM_SERVER_KEY', ''));
        if ($serverKey === '') {
            Log::debug('FCM_SERVER_KEY not set; skipping push', ['user_id' => $userId]);

            return;
        }

        foreach ($tokens as $token) {
            try {
                $response = Http::withHeaders([
                    'Authorization' => 'key='.$serverKey,
                    'Content-Type' => 'application/json',
                ])->post('https://fcm.googleapis.com/fcm/send', [
                    'to' => $token,
                    'priority' => 'high',
                    'content_available' => true,
                    'notification' => [
                        'title' => $title,
                        'body' => $body,
                        'sound' => 'default',
                        'android_channel_id' => 'tich_alerts',
                    ],
                    'data' => array_merge([
                        'title' => $title,
                        'body' => $body,
                        'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                    ], $data),
                    'android' => [
                        'priority' => 'high',
                        'notification' => [
                            'channel_id' => 'tich_alerts',
                            'sound' => 'default',
                            'default_vibrate_timings' => true,
                        ],
                    ],
                ]);

                if ($response->failed()) {
                    Log::warning('FCM push failed', [
                        'user_id' => $userId,
                        'status' => $response->status(),
                        'body' => $response->body(),
                    ]);
                    $json = $response->json();
                    if (($json['results'][0]['error'] ?? null) === 'NotRegistered') {
                        DeviceToken::query()
                            ->where('device_token', $token)
                            ->update(['is_active' => 0]);
                    }
                } else {
                    DeviceToken::query()
                        ->where('device_token', $token)
                        ->update(['last_used_at' => now()]);
                }
            } catch (\Throwable $e) {
                Log::warning('FCM push exception', [
                    'user_id' => $userId,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
