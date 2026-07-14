<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Carbon\Carbon;

class MFAService
{
    /**
     * Generate and send email OTP for MFA
     */
    public function sendEmailOTP(User $user): string
    {
        $otp = $this->generateOTP();
        $expiry = Carbon::now()->addMinutes(10);

        // Store OTP in cache
        Cache::put("mfa_otp_{$user->id}", [
            'otp' => $otp,
            'expires_at' => $expiry,
            'attempts' => 0
        ], 600); // 10 minutes

        // Send email
        Mail::raw("Your TICH ERP verification code is: {$otp}\n\nThis code expires in 10 minutes.", function ($message) use ($user) {
            $message->to($user->email)
                ->subject('TICH ERP - MFA Verification Code');
        });

        return $otp;
    }

    /**
     * Verify email OTP
     */
    public function verifyEmailOTP(User $user, string $otp): bool
    {
        $cachedData = Cache::get("mfa_otp_{$user->id}");

        if (!$cachedData) {
            return false;
        }

        if (Carbon::now()->gt($cachedData['expires_at'])) {
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

        // Clear OTP after successful verification
        Cache::forget("mfa_otp_{$user->id}");

        return true;
    }

    /**
     * Generate 6-digit OTP
     */
    private function generateOTP(): string
    {
        return str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    /**
     * Generate TOTP secret for authenticator apps
     */
    public function generateTOTPSecret(): string
    {
        return strtoupper(Str::random(32));
    }

    /**
     * Generate backup codes for MFA
     */
    public function generateBackupCodes(int $count = 10): array
    {
        $codes = [];
        for ($i = 0; $i < $count; $i++) {
            $codes[] = strtoupper(Str::random(8) . '-' . Str::random(4));
        }
        return $codes;
    }

    /**
     * Enable MFA for user
     */
    public function enableMFA(User $user, string $method, ?string $secret = null, ?array $backupCodes = null): void
    {
        $user->update([
            'mfa_enabled' => 1,
            'mfa_method' => $method,
            'mfa_secret' => $secret,
            'mfa_backup_codes' => $backupCodes,
            'mfa_enabled_at' => now()
        ]);
    }

    /**
     * Disable MFA for user
     */
    public function disableMFA(User $user): void
    {
        $user->update([
            'mfa_enabled' => 0,
            'mfa_method' => null,
            'mfa_secret' => null,
            'mfa_backup_codes' => null,
            'mfa_enabled_at' => null,
            'mfa_last_verified_at' => null
        ]);
    }

    /**
     * Verify TOTP code (requires spomky-labs/otphp package)
     */
    public function verifyTOTP(User $user, string $code): bool
    {
        if (!$user->mfa_secret) {
            return false;
        }

        // This requires the OTPHP package
        // Install: composer require spomky-labs/otphp
        try {
            $otp = \OTPHP\TOTP::create($user->mfa_secret);
            return $otp->verify($code);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Verify backup code
     */
    public function verifyBackupCode(User $user, string $code): bool
    {
        if (!$user->mfa_backup_codes) {
            return false;
        }

        $backupCodes = json_decode($user->mfa_backup_codes, true);

        if (!in_array(strtoupper($code), $backupCodes)) {
            return false;
        }

        // Remove used backup code
        $backupCodes = array_diff($backupCodes, [strtoupper($code)]);
        $user->update(['mfa_backup_codes' => json_encode(array_values($backupCodes))]);

        return true;
    }

    /**
     * Record successful MFA verification
     */
    public function recordVerification(User $user): void
    {
        $user->update(['mfa_last_verified_at' => now()]);
    }

    /**
     * Check if MFA is required for user
     */
    public function isMFARequired(User $user): bool
    {
        // Staff always require MFA
        if ($user->user_type === 'staff') {
            return true;
        }

        // Students can have optional MFA
        if ($user->user_type === 'student') {
            return $user->mfa_enabled;
        }

        // Admin and external always require MFA
        return in_array($user->user_type, ['admin', 'external']);
    }

    /**
     * Get MFA QR code URI for authenticator apps
     */
    public function getTOTPQRCodeURI(User $user): string
    {
        if (!$user->mfa_secret) {
            return '';
        }

        $appName = config('app.name', 'TICH ERP');
        $email = $user->email;

        // This requires the OTPHP package
        try {
            $otp = \OTPHP\TOTP::create($user->mfa_secret);
            $otp->setLabel($email);
            $otp->setIssuer($appName);
            return $otp->getProvisioningUri();
        } catch (\Exception $e) {
            return '';
        }
    }
}
