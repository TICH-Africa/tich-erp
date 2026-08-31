<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\MpesaStkRequest;
use App\Services\ApplicationFeeService;
use App\Services\ApplicationService;
use App\Services\Finance\MpesaStkCallbackService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class ApplicationPaymentController extends Controller
{
    public function __construct(
        protected ApplicationService $applicationService,
        protected ApplicationFeeService $feeService,
        protected MpesaStkCallbackService $callbackService,
    ) {}

    public function show(Request $request): View
    {
        $applicationNumber = old('application_number', $request->query('application_number'));
        $email = old('email', $request->query('email'));
        $applicant = null;

        if ($applicationNumber && $email) {
            $applicant = $this->applicationService->lookupStatus((string) $applicationNumber, (string) $email);
        }

        if ($applicant) {
            session(['apply_pay_applicant_id' => $applicant->id]);
        }

        $stkRequest = null;
        $stkId = (int) session('apply_pay_stk_request_id');

        if ($stkId && $applicant) {
            $stkRequest = MpesaStkRequest::query()
                ->where('id', $stkId)
                ->where('applicant_id', $applicant->id)
                ->first();
        }

        return view('apply.pay', [
            'applicant' => $applicant,
            'applicationNumber' => $applicationNumber,
            'email' => $email,
            'lookedUp' => (bool) ($applicationNumber && $email),
            'instructions' => $applicant ? $this->feeService->paymentInstructions($applicant) : null,
            'mpesaEnabled' => $this->feeService->mpesaEnabled(),
            'mpesaStkBlockers' => $this->feeService->mpesaStkBlockers(),
            'stkRequest' => $stkRequest,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'application_number' => ['required', 'string', 'max:50'],
            'email' => ['required', 'email', 'max:255'],
            'phone_number' => ['required', 'string', 'max:20'],
        ]);

        $applicant = $this->applicationService->lookupStatus(
            $validated['application_number'],
            $validated['email']
        );

        if (! $applicant) {
            return redirect()
                ->route('apply.pay')
                ->withInput()
                ->withErrors(['application_number' => 'No application found for those details.']);
        }

        $payQuery = [
            'application_number' => $applicant->application_number,
            'email' => $applicant->email,
        ];

        if (! $this->feeService->mpesaEnabled()) {
            if (config('finance.mpesa.allow_local_simulate') && app()->environment('local')) {
                $this->feeService->simulateLocalPayment($applicant);

                return redirect()
                    ->route('apply.pay', $payQuery)
                    ->with('status', 'Test payment recorded. Your application fee is verified.');
            }

            return redirect()
                ->route('apply.pay', $payQuery)
                ->withErrors(['phone_number' => 'M-Pesa is not enabled. Add MPESA_* settings to .env (not .env.example) or enable under Finance → M-Pesa settings.']);
        }

        $stkBlockers = $this->feeService->mpesaStkBlockers();

        if ($stkBlockers !== []) {
            return redirect()
                ->route('apply.pay', $payQuery)
                ->withInput()
                ->withErrors(['phone_number' => $stkBlockers[0]]);
        }

        try {
            $stkRequest = $this->feeService->initiateStk($applicant, $validated['phone_number']);
        } catch (Throwable $e) {
            return redirect()
                ->route('apply.pay', $payQuery)
                ->withInput()
                ->withErrors(['phone_number' => $e->getMessage()]);
        }

        session([
            'apply_pay_applicant_id' => $applicant->id,
            'apply_pay_stk_request_id' => $stkRequest->id,
        ]);

        return redirect()
            ->route('apply.pay', $payQuery)
            ->with('status', 'M-Pesa prompt sent to '.$stkRequest->phone.'. Enter your PIN on your phone to complete payment.');
    }

    public function status(MpesaStkRequest $stkRequest): JsonResponse
    {
        abort_unless(
            (int) $stkRequest->applicant_id === (int) session('apply_pay_applicant_id'),
            403
        );

        if ($stkRequest->isPending() && $stkRequest->created_at?->lt(now()->subSeconds(45))) {
            $stkRequest = $this->callbackService->reconcilePending($stkRequest);
        }

        return response()->json([
            'status' => $stkRequest->status,
            'result_code' => $stkRequest->result_code,
            'result_desc' => $stkRequest->result_desc,
            'mpesa_receipt_number' => $stkRequest->mpesa_receipt_number,
            'amount' => (float) $stkRequest->amount,
            'is_complete' => $stkRequest->isTerminal(),
            'is_success' => $stkRequest->status === MpesaStkRequest::STATUS_SUCCESS,
        ]);
    }
}
