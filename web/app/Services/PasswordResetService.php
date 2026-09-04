<?php

namespace App\Services;

use App\Mail\PasswordResetLinkMail;
use App\Models\PasswordResetEscalation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class PasswordResetService
{
    public const MAX_ATTEMPTS = 3;

    public const WINDOW_DAYS = 7;

    public const TOKEN_TTL_MINUTES = 60;

    public function __construct(
        protected AuditService $auditService,
    ) {}

    /**
     * @return array{status: string, message: string}
     */
    public function requestReset(string $email, ?Request $request = null): array
    {
        $email = Str::lower(trim($email));
        $user = User::query()->whereRaw('LOWER(email) = ?', [$email])->first();

        $attempts = $this->attemptCount($email);

        if ($attempts >= self::MAX_ATTEMPTS) {
            $this->escalate($email, $user, $attempts, $request);

            return [
                'status' => 'escalated',
                'message' => 'You have reached the maximum password reset attempts (3 in 7 days). The ICT / Technical team has been notified and will reset your password.',
            ];
        }

        // Always return a generic success to the caller for non-escalated paths.
        if (! $user) {
            $this->recordAttempt($email, null, 'sent', $request);

            return [
                'status' => 'sent',
                'message' => 'If an account exists for that email, a password reset link will be sent shortly.',
            ];
        }

        $token = Str::random(64);
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $email],
            ['token' => Hash::make($token), 'created_at' => now()]
        );

        $this->recordAttempt($email, $user->id, 'sent', $request);

        $resetUrl = url(route('password.reset', [
            'token' => $token,
            'email' => $email,
        ], false));

        try {
            Mail::to($user->email)->send(new PasswordResetLinkMail($resetUrl, self::TOKEN_TTL_MINUTES));
        } catch (\Throwable) {
            // Keep UX generic; admins can use ICT reset if mail fails.
        }

        $this->auditService->log(
            'auth.password_reset.requested',
            'users',
            $user->id,
            null,
            ['email' => $email, 'attempts' => $attempts + 1],
            'Password reset link requested',
            'success',
            $user->id,
            $request
        );

        return [
            'status' => 'sent',
            'message' => 'If an account exists for that email, a password reset link will be sent shortly.',
        ];
    }

    /**
     * @return array{ok: bool, message: string}
     */
    public function resetWithToken(string $email, string $token, string $password, ?Request $request = null): array
    {
        $email = Str::lower(trim($email));
        $row = DB::table('password_reset_tokens')->where('email', $email)->first();

        if (! $row || ! Hash::check($token, $row->token)) {
            return ['ok' => false, 'message' => 'This password reset link is invalid or has already been used.'];
        }

        if ($row->created_at && now()->diffInMinutes($row->created_at) > self::TOKEN_TTL_MINUTES) {
            DB::table('password_reset_tokens')->where('email', $email)->delete();

            return ['ok' => false, 'message' => 'This password reset link has expired. Please request a new one.'];
        }

        $user = User::query()->whereRaw('LOWER(email) = ?', [$email])->first();
        if (! $user) {
            return ['ok' => false, 'message' => 'Account not found.'];
        }

        $user->password_hash = Hash::make($password);
        $user->save();

        DB::table('password_reset_tokens')->where('email', $email)->delete();
        $this->recordAttempt($email, $user->id, 'completed', $request);

        PasswordResetEscalation::query()
            ->where('email', $email)
            ->where('status', PasswordResetEscalation::STATUS_OPEN)
            ->update([
                'status' => PasswordResetEscalation::STATUS_RESOLVED,
                'notes' => 'Resolved via self-service reset link',
                'resolved_at' => now(),
            ]);

        $this->auditService->log(
            'auth.password_reset.completed',
            'users',
            $user->id,
            null,
            ['email' => $email],
            'Password reset completed',
            'success',
            $user->id,
            $request
        );

        return ['ok' => true, 'message' => 'Your password has been reset. You can now sign in.'];
    }

    /**
     * ICT-triggered password reset for a specific user (bypasses self-service limit).
     */
    public function ictReset(User $target, string $newPassword, User $actor, ?Request $request = null): void
    {
        $target->password_hash = Hash::make($newPassword);
        $target->save();

        $email = Str::lower((string) $target->email);
        DB::table('password_reset_tokens')->where('email', $email)->delete();
        $this->recordAttempt($email, $target->id, 'ict_reset', $request);

        PasswordResetEscalation::query()
            ->where(function ($q) use ($target, $email) {
                $q->where('user_id', $target->id)->orWhere('email', $email);
            })
            ->where('status', PasswordResetEscalation::STATUS_OPEN)
            ->update([
                'status' => PasswordResetEscalation::STATUS_RESOLVED,
                'resolved_by_user_id' => $actor->id,
                'resolved_at' => now(),
                'notes' => 'Password reset by ICT',
            ]);

        $this->auditService->log(
            'auth.password_reset.ict',
            'users',
            $target->id,
            null,
            ['email' => $email, 'by' => $actor->id],
            'ICT password reset',
            'success',
            $actor->id,
            $request
        );
    }

    public function attemptCount(string $email): int
    {
        return (int) DB::table('password_reset_attempts')
            ->whereRaw('LOWER(email) = ?', [Str::lower($email)])
            ->where('created_at', '>=', now()->subDays(self::WINDOW_DAYS))
            ->whereIn('status', ['sent', 'blocked', 'escalated'])
            ->count();
    }

    /**
     * @return \Illuminate\Support\Collection<int, PasswordResetEscalation>
     */
    public function openEscalations()
    {
        return PasswordResetEscalation::query()
            ->with('user:id,email,user_type,student_id,staff_id')
            ->where('status', PasswordResetEscalation::STATUS_OPEN)
            ->orderByDesc('created_at')
            ->get();
    }

    private function escalate(string $email, ?User $user, int $attempts, ?Request $request): void
    {
        $this->recordAttempt($email, $user?->id, 'escalated', $request);

        $open = PasswordResetEscalation::query()
            ->where('email', $email)
            ->where('status', PasswordResetEscalation::STATUS_OPEN)
            ->first();

        if ($open) {
            $open->update(['attempt_count' => max($open->attempt_count, $attempts)]);

            return;
        }

        PasswordResetEscalation::query()->create([
            'user_id' => $user?->id,
            'email' => $email,
            'status' => PasswordResetEscalation::STATUS_OPEN,
            'attempt_count' => $attempts,
            'notes' => 'Self-service password reset limit exceeded ('.self::MAX_ATTEMPTS.' in '.self::WINDOW_DAYS.' days).',
        ]);
    }

    private function recordAttempt(string $email, ?int $userId, string $status, ?Request $request): void
    {
        DB::table('password_reset_attempts')->insert([
            'email' => $email,
            'user_id' => $userId,
            'ip_address' => $request?->ip(),
            'status' => $status,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
