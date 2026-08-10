<?php

namespace App\Services;

use App\Mail\MfaVerificationMail;
use App\Models\User;
use App\Support\MailConfig;
use App\Support\ModuleMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use OTPHP\TOTP;
use ParagonIE\ConstantTime\Base32;
use Throwable;

class MFAService
{
    public function __construct(protected AuditService $auditService) {}

    /**
     * @return array{otp: string, sent: bool, error: ?string}
     */
    public function sendEmailOTP(User $user, ?Request $request = null): array
    {
        $otp = $this->generateOTP();
        $expiry = now()->addMinutes(10);

        Cache::put("mfa_otp_{$user->id}", [
            'otp' => $otp,
            'expires_at' => $expiry,
            'attempts' => 0,
        ], 600);

        $delivery = $this->deliverOtpEmail($user, $otp, $request);

        if (config('app.debug')) {
            session()->flash('mfa_dev_code', $otp);

            if (! $delivery['sent']) {
                Log::warning('MFA OTP (dev fallback - email not sent)', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'otp' => $otp,
                    'smtp_error' => $delivery['error'],
                ]);
                session()->flash('mail_error', $delivery['error']);
            }
        }

        return [
            'otp' => $otp,
            'sent' => $delivery['sent'],
            'error' => $delivery['error'],
        ];
    }

    /**
     * @return array{sent: bool, error: ?string}
     */
    private function deliverOtpEmail(User $user, string $otp, ?Request $request = null): array
    {
        if ($issue = MailConfig::moduleIssue(ModuleMail::OTP)) {
            return ['sent' => false, 'error' => $issue];
        }

        try {
            ModuleMail::send(ModuleMail::OTP, $user->email, new MfaVerificationMail($otp, 10));

            $this->auditService->log(
                'auth.mfa.otp_sent',
                'users',
                $user->id,
                null,
                ['mfa_method' => 'email', 'recipient' => $user->email],
                null,
                'success',
                $user->id,
                $request
            );

            return ['sent' => true, 'error' => null];
        } catch (Throwable $e) {
            Log::error('MFA email delivery failed', [
                'user_id' => $user->id,
                'email' => $user->email,
                'message' => $e->getMessage(),
            ]);

            $this->auditService->log(
                'auth.mfa.otp_sent',
                'users',
                $user->id,
                null,
                ['mfa_method' => 'email', 'recipient' => $user->email],
                $e->getMessage(),
                'failure',
                $user->id,
                $request
            );

            return ['sent' => false, 'error' => MailConfig::friendlySmtpError($e->getMessage(), ModuleMail::OTP)];
        }
    }

    public function verifyEmailOTP(User $user, string $otp): bool
    {
        $cachedData = Cache::get("mfa_otp_{$user->id}");

        if (! $cachedData) {
            return false;
        }

        if (now()->gt($cachedData['expires_at'])) {
            Cache::forget("mfa_otp_{$user->id}");

            return false;
        }

        if ($cachedData['attempts'] >= 3) {
            Cache::forget("mfa_otp_{$user->id}");

            return false;
        }

        if ($cachedData['otp'] !== $otp) {
            $cachedData['attempts']++;
            Cache::put("mfa_otp_{$user->id}", $cachedData, 600);

            return false;
        }

        Cache::forget("mfa_otp_{$user->id}");

        return true;
    }

    private function generateOTP(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    public function generateTOTPSecret(): string
    {
        return Base32::encodeUpper(random_bytes(20));
    }

    public function generateBackupCodes(int $count = 10): array
    {
        $codes = [];
        for ($i = 0; $i < $count; $i++) {
            $codes[] = strtoupper(bin2hex(random_bytes(4)).'-'.bin2hex(random_bytes(2)));
        }

        return $codes;
    }

    public function enableMFA(User $user, string $method, ?string $secret = null, ?array $backupCodes = null, ?Request $request = null): void
    {
        $old = [
            'mfa_enabled' => (bool) $user->mfa_enabled,
            'mfa_method' => $user->mfa_method,
        ];

        $user->update([
            'mfa_enabled' => 1,
            'mfa_method' => $method,
            'mfa_secret' => $secret,
            'mfa_secret_temp' => null,
            'mfa_backup_codes' => $backupCodes,
            'mfa_verified' => 1,
            'mfa_enabled_at' => now(),
        ]);

        $this->auditService->log(
            'auth.mfa.enabled',
            'users',
            $user->id,
            $old,
            ['mfa_method' => $method, 'has_backup_codes' => ! empty($backupCodes)],
            null,
            'success',
            $user->id,
            $request
        );
    }

    public function stageTOTPSecret(User $user, string $secret): void
    {
        $user->update(['mfa_secret_temp' => $secret]);
    }

    public function disableMFA(User $user, ?string $reason = null, ?Request $request = null): void
    {
        $old = [
            'mfa_enabled' => (bool) $user->mfa_enabled,
            'mfa_method' => $user->mfa_method,
        ];

        $user->update([
            'mfa_enabled' => 0,
            'mfa_method' => null,
            'mfa_secret' => null,
            'mfa_secret_temp' => null,
            'mfa_backup_codes' => null,
            'mfa_verified' => 0,
            'mfa_enabled_at' => null,
            'mfa_last_verified_at' => null,
        ]);

        $this->auditService->log(
            'auth.mfa.disabled',
            'users',
            $user->id,
            $old,
            ['mfa_enabled' => false],
            $reason,
            'success',
            $user->id,
            $request
        );
    }

    public function verifyTOTP(User $user, string $code): bool
    {
        $secret = $user->mfa_secret ?? $user->mfa_secret_temp;

        if (! $secret) {
            return false;
        }

        try {
            $otp = TOTP::create($secret);

            return $otp->verify($code);
        } catch (\Throwable) {
            return false;
        }
    }

    public function verifyBackupCode(User $user, string $code): bool
    {
        $backupCodes = $user->mfa_backup_codes;

        if (! is_array($backupCodes) || empty($backupCodes)) {
            return false;
        }

        $normalized = strtoupper(str_replace(' ', '', $code));

        if (! in_array($normalized, $backupCodes, true)) {
            return false;
        }

        $remaining = array_values(array_diff($backupCodes, [$normalized]));
        $user->update(['mfa_backup_codes' => $remaining]);

        $this->auditService->log(
            'auth.mfa.backup_used',
            'users',
            $user->id,
            null,
            ['remaining_backup_codes' => count($remaining)],
            'Backup code consumed during MFA verification',
            'success',
            $user->id
        );

        return true;
    }

    public function recordVerification(User $user): void
    {
        $user->update(['mfa_last_verified_at' => now()]);
    }

    public function isMFARequired(User $user): bool
    {
        if (! config('tich.auth.mfa_enabled', true)) {
            return false;
        }

        if ($user->mfa_verified) {
            return false;
        }

        $mandatoryTypes = config('tich.auth.mandatory_mfa_user_types', ['staff', 'student', 'admin', 'external']);

        return in_array($user->user_type, $mandatoryTypes, true);
    }

    public function getTOTPQRCodeURI(User $user, ?string $secret = null): string
    {
        $secret = $secret ?? $user->mfa_secret_temp ?? $user->mfa_secret;

        if (! $secret) {
            return '';
        }

        try {
            $otp = TOTP::create($secret);
            $otp->setLabel($user->email);
            $otp->setIssuer(config('app.name', 'TICH ERP'));

            return $otp->getProvisioningUri();
        } catch (\Throwable) {
            return '';
        }
    }
}
