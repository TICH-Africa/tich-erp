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

        $response = Http::withToken($this->accessToken())
            ->acceptJson()
            ->post($this->settings->baseUrl().'/mpesa/stkpush/v1/processrequest', $payload);

        if (! $response->successful()) {
            Log::error('M-Pesa STK push failed', [
                'status' => $response->status(),
                'body' => $response->json() ?? $response->body(),
                'account_reference' => $accountReference,
            ]);

            throw new RuntimeException(
                $response->json('errorMessage')
                ?? $response->json('ResponseDescription')
                ?? 'M-Pesa STK push request failed.'
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

        $response = Http::withToken($this->accessToken())
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

    private function accessToken(): string
    {
        $cacheKey = 'finance.mpesa.oauth_token.'.$this->settings->environment();

        return Cache::remember($cacheKey, now()->addMinutes(50), function () {
            $key = (string) $this->settings->consumerKey();
            $secret = (string) $this->settings->consumerSecret();

            $response = Http::withBasicAuth($key, $secret)
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
}
