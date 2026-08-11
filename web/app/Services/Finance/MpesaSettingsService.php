<?php

namespace App\Services\Finance;

use App\Models\FinanceMpesaSetting;
use Illuminate\Support\Facades\Cache;

class MpesaSettingsService
{
    public function settings(): FinanceMpesaSetting
    {
        return FinanceMpesaSetting::current();
    }

    public function isEnabled(): bool
    {
        $settings = $this->settings();

        if ($settings->is_enabled) {
            return $this->hasCredentials($settings);
        }

        return (bool) config('finance.mpesa.enabled') && $this->hasEnvCredentials();
    }

    public function environment(): string
    {
        $settings = $this->settings();

        return $settings->shortcode || $settings->consumer_key
            ? $settings->environment
            : (string) config('finance.mpesa.environment', 'sandbox');
    }

    public function baseUrl(): string
    {
        return $this->environment() === 'production'
            ? 'https://api.safaricom.co.ke'
            : 'https://sandbox.safaricom.co.ke';
    }

    public function shortcode(): ?string
    {
        return $this->settings()->shortcode ?: config('finance.mpesa.shortcode');
    }

    public function passkey(): ?string
    {
        $settings = $this->settings();

        return $settings->passkey ?: config('finance.mpesa.passkey');
    }

    public function consumerKey(): ?string
    {
        $settings = $this->settings();

        return $settings->consumer_key ?: config('finance.mpesa.consumer_key');
    }

    public function consumerSecret(): ?string
    {
        $settings = $this->settings();

        return $settings->consumer_secret ?: config('finance.mpesa.consumer_secret');
    }

    public function transactionType(): string
    {
        return $this->settings()->transaction_type ?: 'CustomerPayBillOnline';
    }

    public function accountReferencePrefix(): string
    {
        return $this->settings()->account_reference_prefix ?: 'TICH';
    }

    public function callbackUrl(): string
    {
        $override = $this->settings()->callback_url_override;

        if ($override) {
            return $override;
        }

        $configured = config('finance.mpesa.callback_url');

        if ($configured) {
            return $configured;
        }

        return route('webhooks.mpesa.stk-callback', absolute: true);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(array $data, int $staffId): FinanceMpesaSetting
    {
        $settings = $this->settings();

        $payload = [
            'is_enabled' => (bool) ($data['is_enabled'] ?? false),
            'environment' => in_array($data['environment'] ?? 'sandbox', ['sandbox', 'production'], true)
                ? $data['environment']
                : 'sandbox',
            'shortcode' => $data['shortcode'] ?? null,
            'consumer_key' => $data['consumer_key'] ?? null,
            'transaction_type' => $data['transaction_type'] ?? 'CustomerPayBillOnline',
            'account_reference_prefix' => $data['account_reference_prefix'] ?? 'TICH',
            'callback_url_override' => $data['callback_url_override'] ?? null,
            'updated_by' => $staffId,
        ];

        if (! empty($data['passkey'])) {
            $payload['passkey'] = $data['passkey'];
        }

        if (! empty($data['consumer_secret'])) {
            $payload['consumer_secret'] = $data['consumer_secret'];
        }

        $settings->fill($payload)->save();
        Cache::forget('finance.mpesa.oauth_token');

        return $settings->fresh();
    }

    private function hasCredentials(FinanceMpesaSetting $settings): bool
    {
        $shortcode = $settings->shortcode ?: config('finance.mpesa.shortcode');
        $passkey = $settings->passkey ?: config('finance.mpesa.passkey');
        $key = $settings->consumer_key ?: config('finance.mpesa.consumer_key');
        $secret = $settings->consumer_secret ?: config('finance.mpesa.consumer_secret');

        return filled($shortcode) && filled($passkey) && filled($key) && filled($secret);
    }

    private function hasEnvCredentials(): bool
    {
        return filled(config('finance.mpesa.shortcode'))
            && filled(config('finance.mpesa.passkey'))
            && filled(config('finance.mpesa.consumer_key'))
            && filled(config('finance.mpesa.consumer_secret'));
    }
}
