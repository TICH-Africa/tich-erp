<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\MpesaStkRequest;
use App\Services\Finance\MpesaSettingsService;
use App\Services\Finance\MpesaStkCallbackService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MpesaSettingsController extends Controller
{
    public function __construct(
        protected MpesaSettingsService $settings,
    ) {}

    public function edit(): View
    {
        $settings = $this->settings->settings();

        return view('finance.mpesa.settings', [
            'settings' => $settings,
            'callbackUrl' => $this->settings->callbackUrl(),
            'recentRequests' => MpesaStkRequest::query()
                ->with(['invoice', 'student.applicant'])
                ->latest('id')
                ->limit(20)
                ->get(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'is_enabled' => 'nullable|boolean',
            'environment' => 'required|in:sandbox,production',
            'shortcode' => 'nullable|string|max:20',
            'passkey' => 'nullable|string|max:255',
            'consumer_key' => 'nullable|string|max:255',
            'consumer_secret' => 'nullable|string|max:255',
            'transaction_type' => 'required|in:CustomerPayBillOnline,CustomerBuyGoodsOnline',
            'account_reference_prefix' => 'required|string|max:30',
            'callback_url_override' => 'nullable|url|max:500',
        ]);

        $validated['is_enabled'] = $request->boolean('is_enabled');

        $staffId = (int) ($request->user()?->staff?->id ?? 1);
        $this->settings->update($validated, $staffId);

        return redirect()
            ->route('finance.mpesa.settings')
            ->with('success', 'M-Pesa payment settings saved.');
    }

    public function reconcile(MpesaStkRequest $stkRequest, MpesaStkCallbackService $callbackService): RedirectResponse
    {
        $callbackService->reconcilePending($stkRequest);

        return redirect()
            ->route('finance.mpesa.settings')
            ->with('success', 'STK request status refreshed from Safaricom.');
    }
}
