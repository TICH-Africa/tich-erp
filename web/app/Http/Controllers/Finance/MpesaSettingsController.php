<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\FinanceMpesaSetting;
use App\Models\MpesaC2bTransaction;
use App\Models\MpesaStkRequest;
use App\Services\Finance\FinanceAuditService;
use App\Services\Finance\MpesaC2bService;
use App\Services\Finance\MpesaDarajaService;
use App\Services\Finance\MpesaSettingsService;
use App\Services\Finance\MpesaStkCallbackService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class MpesaSettingsController extends Controller
{
    public function __construct(
        protected MpesaSettingsService $settings,
        protected MpesaC2bService $c2b,
        protected MpesaDarajaService $daraja,
        protected FinanceAuditService $audit,
    ) {}

    public function edit(Request $request): View
    {
        $settings = $this->settings->settings();
        $settings->loadMissing('updater');

        $recentRequests = collect();
        if (Schema::hasTable((new MpesaStkRequest)->getTable())) {
            $recentRequests = MpesaStkRequest::query()
                ->with(['invoice', 'student.applicant'])
                ->latest('id')
                ->limit(20)
                ->get();
        }

        $recentPaybill = collect();
        if (Schema::hasTable((new MpesaC2bTransaction)->getTable())) {
            $recentPaybill = MpesaC2bTransaction::query()
                ->with(['student.applicant', 'payment'])
                ->whereIn('status', [MpesaC2bTransaction::STATUS_UNMATCHED, MpesaC2bTransaction::STATUS_DUPLICATE, 'received'])
                ->latest('id')
                ->limit(10)
                ->get();
        }

        return view('finance.mpesa.settings', [
            'settings' => $settings,
            'callbackUrl' => $this->settings->callbackUrl(),
            'credentialsReady' => $this->credentialsConfigured($settings),
            'recentRequests' => $recentRequests,
            'recentPaybill' => $recentPaybill,
            'paybillSummary' => $this->c2b->paybillSummary(),
            'c2bCallbacks' => $this->c2bCallbacks(),
            'callbackSecretSet' => filled(config('finance.mpesa.callback_secret')),
            'openEditModal' => $request->session()->get('open_mpesa_edit_modal', false) || $request->session()->get('errors')?->any(),
        ]);
    }

    /**
     * Safaricom asks these two URLs about every paybill payment. The shared secret is never
     * printed here; the page only says to append it, so a screenshot cannot leak it.
     *
     * @return list<array<string, string>>
     */
    private function c2bCallbacks(): array
    {
        return [
            [
                'label' => 'Validation URL',
                'url' => (string) route('webhooks.mpesa.c2b-validation'),
                'hint' => 'Safaricom asks here before the money leaves the student. A refusal bounces the payment back.',
            ],
            [
                'label' => 'Confirmation URL',
                'url' => (string) route('webhooks.mpesa.c2b-confirmation'),
                'hint' => 'Safaricom reports here after the money arrived. Only this callback books a payment.',
            ],
        ];
    }

    public function update(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
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

        if ($validator->fails()) {
            return redirect()
                ->route('finance.mpesa.settings')
                ->withInput()
                ->withErrors($validator)
                ->with('open_mpesa_edit_modal', true);
        }

        $validated = $validator->validated();
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

    /**
     * Send both paybill callbacks to Daraja so the officer does not paste them into the
     * Safaricom portal. The shared secret travels in the URL Daraja stores, and is never
     * written to the audit row or shown on the page.
     */
    public function registerCallbacks(Request $request): RedirectResponse
    {
        $validation = $this->c2bCallbackUrl('webhooks.mpesa.c2b-validation');
        $confirmation = $this->c2bCallbackUrl('webhooks.mpesa.c2b-confirmation');

        $result = $this->daraja->registerC2bUrls($validation, $confirmation);

        $this->audit->log(
            'finance.mpesa.paybill.callbacks.registered',
            'finance_mpesa_settings',
            $this->settings->settings()->id,
            null,
            [
                'shortcode' => $this->settings->shortcode(),
                'validation_url' => $this->withoutQuery($validation),
                'confirmation_url' => $this->withoutQuery($confirmation),
                'result_code' => $result['result_code'],
                'result_desc' => $result['result_desc'],
            ],
            $result['ok'] ? 'Safaricom accepted the paybill callback URLs.' : $result['result_desc'],
        );

        return redirect()
            ->route('finance.mpesa.settings')
            ->with($result['ok'] ? 'success' : 'error', $result['ok']
                ? 'Safaricom took both paybill callbacks. It validates the account number before money moves from now on.'
                : ($result['result_code'] === null
                    ? 'Nothing was sent to Safaricom: '.$result['result_desc']
                    : 'Safaricom refused the callback URLs: '.$result['result_desc']));
    }

    /**
     * The route as Daraja must store it, with the shared secret appended when one is set.
     */
    private function c2bCallbackUrl(string $route): string
    {
        $secret = (string) config('finance.mpesa.callback_secret', '');

        return $secret === ''
            ? (string) route($route)
            : (string) route($route, ['token' => $secret]);
    }

    private function withoutQuery(string $url): string
    {
        return explode('?', $url, 2)[0];
    }

    private function credentialsConfigured(FinanceMpesaSetting $settings): bool
    {
        return filled($settings->shortcode ?: config('finance.mpesa.shortcode'))
            && filled($settings->passkey ?: config('finance.mpesa.passkey'))
            && filled($settings->consumer_key ?: config('finance.mpesa.consumer_key'))
            && filled($settings->consumer_secret ?: config('finance.mpesa.consumer_secret'));
    }
}
