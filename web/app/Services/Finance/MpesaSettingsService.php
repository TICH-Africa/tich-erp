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
        if ($this->usesDatabaseCredentials()) {
            return $this->settings()->environment ?: (string) config('finance.mpesa.environment', 'sandbox');
        }

        return (string) config('finance.mpesa.environment', 'sandbox');
    }

    public function baseUrl(): string
    {
        return $this->environment() === 'production'
            ? 'https://api.safaricom.co.ke'
            : 'https://sandbox.safaricom.co.ke';
    }

    public function shortcode(): ?string
    {
        if ($this->usesDatabaseCredentials()) {
            return $this->trimCredential($this->settings()->shortcode)
                ?: $this->trimCredential(config('finance.mpesa.shortcode'));
        }

        return $this->trimCredential(config('finance.mpesa.shortcode'));
    }

    public function passkey(): ?string
    {
        if ($this->usesDatabaseCredentials()) {
            return $this->trimCredential($this->settings()->passkey)
                ?: $this->trimCredential(config('finance.mpesa.passkey'));
        }

        return $this->trimCredential(config('finance.mpesa.passkey'));
    }

    public function consumerKey(): ?string
    {
        if ($this->usesDatabaseCredentials()) {
            return $this->trimCredential($this->settings()->consumer_key)
                ?: $this->trimCredential(config('finance.mpesa.consumer_key'));
        }

        return $this->trimCredential(config('finance.mpesa.consumer_key'));
    }

    public function consumerSecret(): ?string
    {
        if ($this->usesDatabaseCredentials()) {
            return $this->trimCredential($this->settings()->consumer_secret)
                ?: $this->trimCredential(config('finance.mpesa.consumer_secret'));
        }

        return $this->trimCredential(config('finance.mpesa.consumer_secret'));
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
     * @return list<string> Blockers that prevent STK push (empty list = ready).
     */
    public function stkPushBlockers(): array
    {
        $blockers = [];

        if (! $this->isEnabled()) {
            $blockers[] = 'M-Pesa is not enabled. Set MPESA_ENABLED=true in .env (not .env.example) or enable it under Finance → M-Pesa settings with all credentials.';

            return $blockers;
        }

        if (! filled($this->shortcode()) || ! filled($this->passkey()) || ! filled($this->consumerKey()) || ! filled($this->consumerSecret())) {
            $blockers[] = 'M-Pesa credentials are incomplete. Shortcode, passkey, consumer key, and consumer secret are all required.';
        }

        $callbackIssue = $this->callbackUrlIssue();

        if ($callbackIssue !== null) {
            $blockers[] = $callbackIssue;
        }

        return $blockers;
    }

    public function callbackUrlIssue(): ?string
    {
        $url = trim($this->callbackUrl());

        if ($url === '' || ! filter_var($url, FILTER_VALIDATE_URL)) {
            return 'M-Pesa callback URL is missing. Set MPESA_CALLBACK_URL in .env to a public HTTPS URL. Production: https://tich.africa/tich-mpesa-stk-callback.php';
        }

        $host = strtolower((string) parse_url($url, PHP_URL_HOST));

        if (in_array($host, ['127.0.0.1', 'localhost', '::1'], true)) {
            return 'M-Pesa cannot send STK prompts while the callback URL points to localhost ('.$url.'). Use https://tich.africa/tich-mpesa-stk-callback.php or another public HTTPS URL.';
        }

        if (! str_starts_with(strtolower($url), 'https://')) {
            return 'M-Pesa callback URL must use HTTPS. Current value: '.$url;
        }

        return null;
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
        $this->clearOAuthCache();

        return $settings->fresh();
    }

    public function clearOAuthCache(): void
    {
        foreach (['sandbox', 'production'] as $environment) {
            Cache::forget('finance.mpesa.oauth_token.'.$environment);
        }
    }

    /**
     * Finance UI credentials apply only when M-Pesa is enabled there.
     * Otherwise use .env only — avoids mixing a DB consumer key with an .env passkey.
     */
    private function usesDatabaseCredentials(): bool
    {
        return (bool) $this->settings()->is_enabled;
    }

    private function trimCredential(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed !== '' ? $trimmed : null;
    }

    private function hasCredentials(FinanceMpesaSetting $settings): bool
    {
        $shortcode = $this->trimCredential($settings->shortcode) ?: $this->trimCredential(config('finance.mpesa.shortcode'));
        $passkey = $this->trimCredential($settings->passkey) ?: $this->trimCredential(config('finance.mpesa.passkey'));
        $key = $this->trimCredential($settings->consumer_key) ?: $this->trimCredential(config('finance.mpesa.consumer_key'));
        $secret = $this->trimCredential($settings->consumer_secret) ?: $this->trimCredential(config('finance.mpesa.consumer_secret'));

        return filled($shortcode) && filled($passkey) && filled($key) && filled($secret);
    }

    private function hasEnvCredentials(): bool
    {
        return filled($this->trimCredential(config('finance.mpesa.shortcode')))
            && filled($this->trimCredential(config('finance.mpesa.passkey')))
            && filled($this->trimCredential(config('finance.mpesa.consumer_key')))
            && filled($this->trimCredential(config('finance.mpesa.consumer_secret')));
    }
}
