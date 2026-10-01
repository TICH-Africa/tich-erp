<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Services\Finance\MpesaStkCallbackService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MpesaStkCallbackController extends Controller
{
    public function __invoke(Request $request, MpesaStkCallbackService $callbackService): JsonResponse
    {
        if (! $this->callbackSecretIsValid($request)) {
            Log::warning('M-Pesa STK callback rejected: invalid or missing shared secret', [
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'ResultCode' => 1,
                'ResultDesc' => 'Unauthorized',
            ], 401);
        }

        $payload = $request->all();

        Log::info('M-Pesa STK callback received', [
            'checkout_request_id' => data_get($payload, 'Body.stkCallback.CheckoutRequestID'),
            'result_code' => data_get($payload, 'Body.stkCallback.ResultCode'),
        ]);

        try {
            $callbackService->handle($payload);
        } catch (\Throwable $e) {
            Log::error('M-Pesa STK callback processing failed', [
                'message' => $e->getMessage(),
            ]);
        }

        return response()->json([
            'ResultCode' => 0,
            'ResultDesc' => 'Accepted',
        ]);
    }

    private function callbackSecretIsValid(Request $request): bool
    {
        $expected = (string) config('finance.mpesa.callback_secret', '');

        // When no secret is configured (local/sandbox), accept callbacks.
        if ($expected === '') {
            return true;
        }

        $provided = (string) (
            $request->header('X-Mpesa-Callback-Token')
            ?: $request->query('token', '')
        );

        return $provided !== '' && hash_equals($expected, $provided);
    }
}
