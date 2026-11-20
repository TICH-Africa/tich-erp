<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Services\ApplicationFeeService;
use App\Services\ApplicationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ApplicationPaymentController extends Controller
{
    public function __construct(
        protected ApplicationService $applicationService,
        protected ApplicationFeeService $feeService,
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

        return view('apply.pay', [
            'applicant' => $applicant,
            'applicationNumber' => $applicationNumber,
            'email' => $email,
            'lookedUp' => (bool) ($applicationNumber && $email),
            'instructions' => $applicant ? $this->feeService->paymentInstructions($applicant) : null,
            'canSimulate' => config('finance.mpesa.allow_local_simulate') && app()->environment('local'),
        ]);
    }

    /**
     * Only the local test path posts a payment from this page. Everywhere else the
     * applicant sends the money on the paybill and Safaricom's confirmation books it.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'application_number' => ['required', 'string', 'max:50'],
            'email' => ['required', 'email', 'max:255'],
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

        if (! config('finance.mpesa.allow_local_simulate') || ! app()->environment('local')) {
            return redirect()
                ->route('apply.pay', $payQuery)
                ->withErrors(['application' => 'Pay the application fee on the M-Pesa paybill shown on this page.']);
        }

        $this->feeService->simulateLocalPayment($applicant);

        return redirect()
            ->route('apply.pay', $payQuery)
            ->with('status', 'Test payment recorded. Your application fee is verified.');
    }
}
