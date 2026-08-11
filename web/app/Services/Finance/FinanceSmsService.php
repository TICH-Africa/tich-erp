<?php

namespace App\Services\Finance;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FinanceSmsService
{
    public function isEnabled(): bool
    {
        return (bool) config('finance.ar.sms_enabled', false)
            && filled(config('finance.ar.sms_api_url'));
    }

    public function send(string $phone, string $message): bool
    {
        $normalized = preg_replace('/\D+/', '', $phone) ?? '';

        if ($normalized === '') {
            return false;
        }

        if (! $this->isEnabled()) {
            Log::info('Finance SMS (dry-run)', [
                'phone' => $normalized,
                'message' => $message,
            ]);

            return true;
        }

        try {
            $response = Http::timeout(15)
                ->withHeaders($this->headers())
                ->post((string) config('finance.ar.sms_api_url'), [
                    'phone' => $normalized,
                    'message' => $message,
                    'sender' => config('finance.ar.sms_sender'),
                ]);

            if (! $response->successful()) {
                Log::warning('Finance SMS failed', [
                    'phone' => $normalized,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::error('Finance SMS exception', [
                'phone' => $normalized,
                'message' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * @return array<string, string>
     */
    private function headers(): array
    {
        $headers = [];

        if ($token = config('finance.ar.sms_api_token')) {
            $headers['Authorization'] = 'Bearer '.$token;
        }

        return $headers;
    }
}
