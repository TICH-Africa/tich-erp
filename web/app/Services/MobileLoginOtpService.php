<?php

namespace App\Services;

use App\Mail\MobileAppLoginOtpMail;
use App\Models\User;
use App\Support\MailConfig;
use App\Support\ModuleMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class MobileLoginOtpService
{
    public function __construct(protected AuditService $auditService) {}

    public function expiryMinutes(): int
    {
        return max(1, (int) config('tich.auth.mobile_otp_expiry_minutes', 15));
    }

    /**
     * @return array{otp: string, sent: bool, error: ?string, expires_minutes: int}
     */
    public function send(User $user, ?Request $request = null): array
    {
        $otp = $this->generateOtp();
        $minutes = $this->expiryMinutes();
        $expiry = now()->addMinutes($minutes);

        Cache::put($this->cacheKey($user), [
            'otp' => $otp,
            'expires_at' => $expiry,
            'attempts' => 0,
        ], $minutes * 60);

        $delivery = $this->deliverEmail($user, $otp, $request, $minutes);

        if (config('app.debug')) {
            Log::info('Mobile login OTP (dev)', [
                'user_id' => $user->id,
                'email' => $user->email,
                'otp' => $otp,
                'smtp_error' => $delivery['error'],
            ]);
        }

        return [
            'otp' => $otp,
            'sent' => $delivery['sent'],
            'error' => $delivery['error'],
            'expires_minutes' => $minutes,
        ];
    }

    /**
     * @return array{sent: bool, error: ?string}
     */
    private function deliverEmail(User $user, string $otp, ?Request $request, int $minutes): array
    {
        if ($issue = MailConfig::moduleIssue(ModuleMail::OTP)) {
            return ['sent' => false, 'error' => $issue];
        }

        try {
            ModuleMail::send(ModuleMail::OTP, $user->email, new MobileAppLoginOtpMail($otp, $minutes));

            $this->auditService->log(
                'auth.mobile.otp_sent',
                'users',
                $user->id,
                null,
                ['recipient' => $user->email],
                null,
                'success',
                $user->id,
                $request
            );

            return ['sent' => true, 'error' => null];
        } catch (Throwable $e) {
            Log::error('Mobile login OTP email failed', [
                'user_id' => $user->id,
                'email' => $user->email,
                'message' => $e->getMessage(),
            ]);

            $this->auditService->log(
                'auth.mobile.otp_sent',
                'users',
                $user->id,
                null,
                ['recipient' => $user->email],
                $e->getMessage(),
                'failure',
                $user->id,
                $request
            );

            return [
                'sent' => false,
                'error' => MailConfig::friendlySmtpError($e->getMessage(), ModuleMail::OTP),
            ];
        }
    }

    public function verify(User $user, string $code): bool
    {
        $code = trim($code);
        if (! preg_match('/^\d{6}$/', $code)) {
            return false;
        }

        $cachedData = Cache::get($this->cacheKey($user));

        if (! $cachedData) {
            return false;
        }

        if (now()->gt($cachedData['expires_at'])) {
            Cache::forget($this->cacheKey($user));

            return false;
        }

        if ($cachedData['attempts'] >= 5) {
            Cache::forget($this->cacheKey($user));

            return false;
        }

        if ($cachedData['otp'] !== $code) {
            $cachedData['attempts']++;
            Cache::put($this->cacheKey($user), $cachedData, $this->expiryMinutes() * 60);

            return false;
        }

        Cache::forget($this->cacheKey($user));

        return true;
    }

    public function maskEmail(string $email): string
    {
        if (! str_contains($email, '@')) {
            return $email;
        }

        [$local, $domain] = explode('@', $email, 2);
        $visible = substr($local, 0, min(2, strlen($local)));
        $masked = $visible.str_repeat('•', max(1, strlen($local) - strlen($visible)));

        return $masked.'@'.$domain;
    }

    private function cacheKey(User $user): string
    {
        return "mobile_login_otp_{$user->id}";
    }

    private function generateOtp(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }
}