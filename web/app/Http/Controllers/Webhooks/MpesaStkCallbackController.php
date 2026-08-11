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
}
