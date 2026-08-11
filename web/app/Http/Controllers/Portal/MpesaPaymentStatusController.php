<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\MpesaStkRequest;
use App\Services\Finance\MpesaStkCallbackService;
use App\Services\StudentPortalService;
use Illuminate\Http\JsonResponse;

class MpesaPaymentStatusController extends Controller
{
    public function __construct(
        protected StudentPortalService $studentPortal,
        protected MpesaStkCallbackService $callbackService,
    ) {}

    public function __invoke(MpesaStkRequest $stkRequest): JsonResponse
    {
        $student = $this->studentPortal->studentForUser(request()->user());
        abort_unless($student && (int) $stkRequest->student_id === (int) $student->id, 403);

        if ($stkRequest->isPending() && $stkRequest->created_at?->lt(now()->subSeconds(45))) {
            $stkRequest = $this->callbackService->reconcilePending($stkRequest);
        }

        return response()->json([
            'status' => $stkRequest->status,
            'result_code' => $stkRequest->result_code,
            'result_desc' => $stkRequest->result_desc,
            'mpesa_receipt_number' => $stkRequest->mpesa_receipt_number,
            'amount' => (float) $stkRequest->amount,
            'invoice_number' => $stkRequest->invoice?->invoice_number,
            'is_complete' => $stkRequest->isTerminal(),
            'is_success' => $stkRequest->status === MpesaStkRequest::STATUS_SUCCESS,
        ]);
    }
}
