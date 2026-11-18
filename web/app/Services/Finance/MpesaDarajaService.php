<?php

namespace App\Services\Finance;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class MpesaDarajaService
{
    public function __construct(
        protected MpesaSettingsService $settings,
    ) {}

    public function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if (str_starts_with($digits, '0') && strlen($digits) === 10) {
            return '254'.substr($digits, 1);
        }

        if (str_starts_with($digits, '254')) {
            return $digits;
        }

        if (str_starts_with($digits, '7') && strlen($digits) === 9) {
            return '254'.$digits;
        }

        if (str_starts_with($digits, '1') && strlen($digits) === 9) {
            return '254'.$digits;
        }

        abort(422, 'Enter a valid Kenyan M-Pesa number (e.g. 0712345678).');
    }

    /**
     * @return array{merchant_request_id: string, checkout_request_id: string, response_code: string, response_description: string}
     */
    public function stkPush(float $amount, string $phone, string $accountReference, string $description): array
    {
        $shortcode = (string) $this->settings->shortcode();
        $passkey = (string) $this->settings->passkey();
        $timestamp = now()->format('YmdHis');
        $password = base64_encode($shortcode.$passkey.$timestamp);
        $normalizedPhone = $this->normalizePhone($phone);

        $payload = [
            'BusinessShortCode' => $shortcode,
            'Password' => $password,
            'Timestamp' => $timestamp,
            'TransactionType' => $this->settings->transactionType(),
            'Amount' => (int) round($amount),
            'PartyA' => $normalizedPhone,
            'PartyB' => $shortcode,
            'PhoneNumber' => $normalizedPhone,
            'CallBackURL' => $this->settings->callbackUrl(),
            'AccountReference' => substr($accountReference, 0, 12),
            'TransactionDesc' => substr($description, 0, 50),
        ];

        $response = Http::timeout(30)->connectTimeout(5)
            ->withToken($this->accessToken())
            ->acceptJson()
            ->post($this->settings->baseUrl().'/mpesa/stkpush/v1/processrequest', $payload);

        if (! $response->successful()) {
            Log::error('M-Pesa STK push failed', [
                'status' => $response->status(),
                'body' => $response->json() ?? $response->body(),
                'account_reference' => $accountReference,
            ]);

            throw new RuntimeException(
                $this->formatStkFailureMessage($response)
            );
        }

        $body = $response->json();

        if (($body['ResponseCode'] ?? null) !== '0') {
            throw new RuntimeException($body['ResponseDescription'] ?? 'M-Pesa rejected the STK push request.');
        }

        return [
            'merchant_request_id' => (string) ($body['MerchantRequestID'] ?? ''),
            'checkout_request_id' => (string) ($body['CheckoutRequestID'] ?? ''),
            'response_code' => (string) ($body['ResponseCode'] ?? ''),
            'response_description' => (string) ($body['ResponseDescription'] ?? ''),
        ];
    }

    /**
     * @return array{result_code: int|null, result_desc: string|null, metadata: array<string, mixed>}
     */
    public function stkQuery(string $checkoutRequestId): array
    {
        $shortcode = (string) $this->settings->shortcode();
        $passkey = (string) $this->settings->passkey();
        $timestamp = now()->format('YmdHis');
        $password = base64_encode($shortcode.$passkey.$timestamp);

        $response = Http::timeout(15)->connectTimeout(5)
            ->withToken($this->accessToken())
            ->acceptJson()
            ->post($this->settings->baseUrl().'/mpesa/stkpushquery/v1/query', [
                'BusinessShortCode' => $shortcode,
                'Password' => $password,
                'Timestamp' => $timestamp,
                'CheckoutRequestID' => $checkoutRequestId,
            ]);

        if (! $response->successful()) {
            Log::warning('M-Pesa STK query failed', [
                'checkout_request_id' => $checkoutRequestId,
                'status' => $response->status(),
                'body' => $response->json() ?? $response->body(),
            ]);

            return [
                'result_code' => null,
                'result_desc' => 'STK query request failed.',
                'metadata' => [],
            ];
        }

        $body = $response->json();

        return [
            'result_code' => isset($body['ResultCode']) ? (int) $body['ResultCode'] : null,
            'result_desc' => $body['ResultDesc'] ?? null,
            'metadata' => [],
        ];
    }

    /**
     * Hand both paybill callback URLs to Daraja in one call, so an officer does not have
     * to paste them into the Safaricom portal by hand. Safaricom answers the result codes
     * documented for C2B URL registration, and a code of 0 means it took them.
     *
     * @return array{ok:bool,result_code:string|null,result_desc:string}
     */
    public function registerC2bUrls(string $validationUrl, string $confirmationUrl): array
    {
        $shortcode = (string) $this->settings->shortcode();

        if ($shortcode === '') {
            return [
                'ok' => false,
                'result_code' => null,
                'result_desc' => 'Save the paybill shortcode on this page before registering the callbacks.',
            ];
        }

        foreach (['Validation' => $validationUrl, 'Confirmation' => $confirmationUrl] as $label => $url) {
            $issue = $this->publicHttpsIssue($url);

            if ($issue !== null) {
                return ['ok' => false, 'result_code' => null, 'result_desc' => $label.' URL: '.$issue];
            }
        }

        try {
            $token = $this->accessToken();
        } catch (RuntimeException $e) {
            return ['ok' => false, 'result_code' => 'auth', 'result_desc' => $e->getMessage()];
        }

        $response = Http::timeout(20)->connectTimeout(5)
            ->withToken($token)
            ->acceptJson()
            ->post($this->settings->baseUrl().'/mpesa/c2b/v1/registerurl', [
                'ShortCode' => $shortcode,
                'CommandName' => $this->settings->transactionType() === 'CustomerBuyGoodsOnline'
                    ? 'BusinessBuyGoods'
                    : 'CustomerPayBillOnline',
                'ConfirmationURL' => $confirmationUrl,
                'ValidationURL' => $validationUrl,
                'ResponseType' => 0,
            ]);

        $body = $response->json() ?? [];

        if (! $response->successful()) {
            Log::error('M-Pesa paybill callback registration failed', [
                'status' => $response->status(),
                'body' => $body ?: $response->body(),
            ]);

            return [
                'ok' => false,
                'result_code' => isset($body['errorCode']) ? (string) $body['errorCode'] : (string) $response->status(),
                'result_desc' => (string) ($body['errorMessage'] ?? $body['ResultDesc'] ?? 'Safaricom answered with HTTP '.$response->status().'.'),
            ];
        }

        $code = isset($body['ResultCode']) ? (string) $body['ResultCode'] : null;

        return [
            'ok' => $code === '0',
            'result_code' => $code,
            'result_desc' => (string) ($body['ResultDesc'] ?? 'Safaricom did not answer.'),
        ];
    }

    private function publicHttpsIssue(string $url): ?string
    {
        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            return 'is not a complete URL.';
        }

        if (! str_starts_with(strtolower($url), 'https://')) {
            return 'must use HTTPS.';
        }

        if (in_array(strtolower((string) parse_url($url, PHP_URL_HOST)), ['127.0.0.1', 'localhost', '::1'], true)) {
            return 'points at this computer, which Safaricom cannot reach. Set APP_URL to the public site address first.';
        }

        return null;
    }

    public function accessToken(): string
    {
        $cacheKey = 'finance.mpesa.oauth_token.'.$this->settings->environment();

        return Cache::remember($cacheKey, now()->addMinutes(50), function () {
            $key = (string) $this->settings->consumerKey();
            $secret = (string) $this->settings->consumerSecret();

            $response = Http::timeout(20)->connectTimeout(5)
                ->withBasicAuth($key, $secret)
                ->acceptJson()
                ->get($this->settings->baseUrl().'/oauth/v1/generate', [
                    'grant_type' => 'client_credentials',
                ]);

            if (! $response->successful()) {
                Log::error('M-Pesa OAuth failed', [
                    'status' => $response->status(),
                    'body' => $response->json() ?? $response->body(),
                ]);

                throw new RuntimeException('Could not authenticate with Safaricom Daraja API.');
            }

            $token = $response->json('access_token');

            if (! $token) {
                throw new RuntimeException('Safaricom Daraja API did not return an access token.');
            }

            return $token;
        });
    }

    private function formatStkFailureMessage(\Illuminate\Http\Client\Response $response): string
    {
        $message = $response->json('errorMessage')
            ?? $response->json('ResponseDescription')
            ?? 'M-Pesa STK push request failed.';

        if (stripos($message, 'wrong credentials') !== false) {
            $message .= ' Check that shortcode, passkey, consumer key, and consumer secret all come from the same Daraja app and environment (sandbox vs production). '
                .'In sandbox use shortcode 174379 and the Lipa Na M-Pesa passkey from your Daraja Test Credentials page. '
                .'If you use .env, disable Finance → M-Pesa settings or enable it with the full matching set. '
                .'Then run: php artisan cache:clear';
        }

        return $message;
    }
}
