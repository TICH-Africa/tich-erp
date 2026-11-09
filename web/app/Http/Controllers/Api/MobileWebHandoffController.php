<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\MobileWebHandoffService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class MobileWebHandoffController extends Controller
{
    public function __construct(
        protected MobileWebHandoffService $handoffs,
    ) {}

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'path' => ['required', 'string', 'max:500'],
        ]);

        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        try {
            $result = $this->handoffs->issue($user, (string) $validated['path']);
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'url' => $result['url'],
            'expires_in_seconds' => 300,
        ]);
    }
}
