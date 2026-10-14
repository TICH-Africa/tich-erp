<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DeviceToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class DeviceTokenController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        abort_unless(Schema::hasTable('device_tokens'), 503, 'Device tokens are unavailable.');

        $validated = $request->validate([
            'device_token' => 'required|string|max:500',
            'platform' => 'required|in:android,ios,web',
            'device_name' => 'nullable|string|max:200',
            'app_version' => 'nullable|string|max:20',
        ]);

        $token = DeviceToken::query()->updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'device_token' => $validated['device_token'],
                'platform' => $validated['platform'],
            ],
            [
                'device_name' => $validated['device_name'] ?? null,
                'app_version' => $validated['app_version'] ?? null,
                'is_active' => 1,
                'last_used_at' => now(),
            ]
        );

        return response()->json([
            'message' => 'Device registered for push notifications.',
            'id' => $token->id,
        ]);
    }

    public function destroy(Request $request): JsonResponse
    {
        abort_unless(Schema::hasTable('device_tokens'), 503);

        $validated = $request->validate([
            'device_token' => 'required|string|max:500',
        ]);

        DeviceToken::query()
            ->where('user_id', $request->user()->id)
            ->where('device_token', $validated['device_token'])
            ->update(['is_active' => 0]);

        return response()->json(['message' => 'Device unregistered.']);
    }
}
