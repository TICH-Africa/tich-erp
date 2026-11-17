<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Services\Finance\MpesaC2bService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Safaricom's two paybill callbacks. Validation decides before the money moves, so a
 * refusal bounces it to the payer; confirmation only ever reports money already taken.
 */
class MpesaC2bController extends Controller
{
    public function validation(Request $request, MpesaC2bService $c2b): JsonResponse
    {
        if (! $this->callbackSecretIsValid($request)) {
            Log::warning('M-Pesa paybill validation rejected: invalid or missing shared secret', [
                'ip' => $request->ip(),
            ]);

            return response()->json(MpesaC2bService::REJECT);
        }

        try {
            $result = $c2b->validate($request->all());
        } catch (\Throwable $e) {
            // Never bounce a payer's money because our own check failed. Take it and
            // leave the receipt on the log for Finance to trace.
            Log::error('M-Pesa paybill validation check failed', ['message' => $e->getMessage()]);

            $result = MpesaC2bService::ACCEPT;
        }

        return response()->json($result);
    }

    public function confirmation(Request $request, MpesaC2bService $c2b): JsonResponse
    {
        if (! $this->callbackSecretIsValid($request)) {
            Log::warning('M-Pesa paybill confirmation rejected: invalid or missing shared secret', [
                'ip' => $request->ip(),
            ]);

            return $this->failure('Unauthorized');
        }

        try {
            $result = $c2b->confirm($request->all());
        } catch (\Throwable $e) {
            // Ask Safaricom to retry the notification; booking a receipt twice is guarded.
            Log::error('M-Pesa paybill confirmation could not be booked', ['message' => $e->getMessage()]);

            return $this->failure('Could not record the payment, please retry.');
        }

        return response()->json($result);
    }

    /**
     * @return JsonResponse<array{ResultCode:string,ResultDesc:string}>
     */
    private function failure(string $reason): JsonResponse
    {
        return response()->json([
            'ResultCode' => '1',
            'ResultDesc' => $reason,
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
