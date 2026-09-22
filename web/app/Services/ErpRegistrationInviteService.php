<?php

namespace App\Services;

use App\Mail\ErpRegistrationInvitationEmail;
use App\Models\ErpRegistrationInvitation;
use App\Models\Staff;
use App\Models\User;
use App\Support\ModuleMail;
use Illuminate\Support\Str;

class ErpRegistrationInviteService
{
    /**
     * @return array{success: bool, message: string, invitation?: ErpRegistrationInvitation}
     */
    public function send(string $email, User $invitedBy, string $mailModule): array
    {
        $email = strtolower(trim($email));

        if (! in_array($mailModule, ['ict', 'hr'], true)) {
            return ['success' => false, 'message' => 'Invalid mail module for registration invite.'];
        }

        if (User::query()->whereRaw('LOWER(email) = ?', [$email])->exists()) {
            return [
                'success' => false,
                'message' => 'An ERP account already exists for this email. They can sign in or use forgot password.',
            ];
        }

        $staff = $this->findStaffByPersonalEmail($email);

        if ($staff?->user_id) {
            return [
                'success' => false,
                'message' => 'This employee already has an ERP account. They can sign in or use forgot password.',
            ];
        }

        $hadPriorInvite = ErpRegistrationInvitation::query()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->exists();

        // Expire any still-open links so only the new invite works.
        ErpRegistrationInvitation::query()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->update(['expires_at' => now()]);

        $invitation = ErpRegistrationInvitation::query()->create([
            'staff_id' => $staff?->id,
            'email' => $email,
            'token' => Str::random(48),
            'sent_via_module' => $mailModule,
            'invited_by' => $invitedBy->id,
            'expires_at' => now()->addDays((int) config('tich.erp_registration.invite_days', 14)),
        ]);

        // Same delivery path for HR and ICT invites: notification@ reaches external inboxes;
        // hr@ / ict@ are accepted by SMTP but often never arrive.
        $deliveryModule = (string) config('tich-mail.invite_delivery_module', ModuleMail::NOTIFICATION);
        if ($deliveryModule === '' || ! config()->has("tich-mail.modules.{$deliveryModule}")) {
            $deliveryModule = ModuleMail::NOTIFICATION;
        }

        $delivery = ModuleMail::trySend(
            $deliveryModule,
            $email,
            new ErpRegistrationInvitationEmail($invitation, $staff, $deliveryModule),
        );

        if (! $delivery['sent']) {
            $invitationId = $invitation->id;
            $invitation->delete();

            $this->auditInvite(
                'auth.registration_invite.mail_failed',
                $invitationId,
                [
                    'email' => $email,
                    'staff_id' => $staff?->id,
                    'mail_module' => $mailModule,
                    'delivery_module' => $deliveryModule,
                    'error' => $delivery['error'],
                ],
                'failure',
                $invitedBy->id,
            );

            return [
                'success' => false,
                'message' => 'Invitation email could not be sent to '.$email.'. '
                    .($delivery['error'] ?? 'Check MAIL_HOST and MAIL_NOTIFICATION_* credentials in .env, then run php artisan config:clear.'),
            ];
        }

        $this->auditInvite(
            'auth.registration_invite.sent',
            $invitation->id,
            [
                'email' => $email,
                'staff_id' => $staff?->id,
                'mail_module' => $mailModule,
                'delivery_module' => $deliveryModule,
                'resent' => $hadPriorInvite,
            ],
            'success',
            $invitedBy->id,
        );

        if ($hadPriorInvite) {
            $message = $staff
                ? "Registration invitation re-sent to {$email} for {$staff->fullName()}. Previous unused links are no longer valid."
                : "Registration invitation re-sent to {$email}. Previous unused links are no longer valid.";
        } else {
            $message = $staff
                ? "Registration invitation sent to {$email} for {$staff->fullName()}."
                : "Registration invitation sent to {$email}.";
        }

        return [
            'success' => true,
            'message' => $message,
            'invitation' => $invitation,
        ];
    }

    /**
     * Invite a specific staff record using the personal email HR captured.
     *
     * @return array{success: bool, message: string, invitation?: ErpRegistrationInvitation}
     */
    public function sendForStaff(Staff $staff, User $invitedBy, string $mailModule = 'hr'): array
    {
        $email = strtolower(trim((string) $staff->primary_email));

        if ($email === '') {
            return [
                'success' => false,
                'message' => 'Add a personal email on this staff record before sending an invite.',
            ];
        }

        if ($staff->user_id) {
            return [
                'success' => false,
                'message' => 'This employee already has an ERP account. They can sign in or use forgot password.',
            ];
        }

        $result = $this->send($email, $invitedBy, $mailModule);

        if (! empty($result['invitation']) && empty($result['invitation']->staff_id)) {
            $result['invitation']->update(['staff_id' => $staff->id]);
        }

        return $result;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function auditInvite(string $action, int $invitationId, array $payload, string $status, int $userId): void
    {
        try {
            app(AuditService::class)->log(
                $action,
                'erp_registration_invitations',
                $invitationId,
                null,
                $payload,
                null,
                $status,
                $userId,
            );
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Registration invite audit failed', [
                'action' => $action,
                'invitation_id' => $invitationId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Re-send an invitation for a previous invite row (pending or expired only).
     *
     * @return array{success: bool, message: string, invitation?: ErpRegistrationInvitation}
     */
    public function resend(ErpRegistrationInvitation $invitation, User $invitedBy, string $mailModule): array
    {
        if ($invitation->used_at !== null) {
            return [
                'success' => false,
                'message' => 'This invitation was already used. The person can sign in or use forgot password.',
            ];
        }

        return $this->send($invitation->email, $invitedBy, $mailModule);
    }

    public function findActiveByToken(string $token): ?ErpRegistrationInvitation
    {
        $invitation = ErpRegistrationInvitation::query()
            ->with(['staff.department'])
            ->where('token', $token)
            ->first();

        if (! $invitation || ! $invitation->isActive()) {
            return null;
        }

        return $invitation;
    }

    public function completeRegistration(ErpRegistrationInvitation $invitation, string $password, ?\Illuminate\Http\Request $request = null): User
    {
        abort_if(
            User::query()->whereRaw('LOWER(email) = ?', [strtolower($invitation->email)])->exists(),
            422,
            'An ERP account already exists for this email.',
        );

        $staff = $invitation->staff ?? $this->findStaffByPersonalEmail($invitation->email);

        abort_if($staff?->user_id, 422, 'This employee already has an ERP account.');

        // Invited accounts are employees - ensure a staff record exists so they can open My Employee Portal.
        if (! $staff) {
            try {
                $staff = app(StaffLifecycleService::class)->createProvisionalInviteStaff(
                    $invitation->email,
                    $invitation->invited_by,
                );
            } catch (\RuntimeException $e) {
                abort(422, $e->getMessage());
            }
            $invitation->update(['staff_id' => $staff->id]);
        } else {
            app(StaffLifecycleService::class)->ensureOnboardingRecord($staff);
        }

        $user = User::query()->create([
            'email' => $invitation->email,
            'user_type' => 'staff',
            'password_hash' => \Illuminate\Support\Facades\Hash::make($password),
            'staff_id' => $staff->id,
            'is_active' => 1,
            'mfa_enabled' => 0,
            'mfa_verified' => true,
        ]);

        app(StaffLifecycleService::class)->ensureEmployeeIdentity($staff, $user);

        if ($staff->primary_email !== $invitation->email) {
            $staff->update(['primary_email' => $invitation->email]);
        }

        // Never invent organisation (@tich.africa) email on invite registration.

        $invitation->update(['staff_id' => $staff->id]);

        app(RBACService::class)->assignDefaultRole($user);

        app(RBACService::class)->reconcileStaffEmploymentDepartment($user->fresh(['staff']));

        $invitation->update(['used_at' => now()]);

        app(AuditService::class)->log(
            'auth.register',
            'users',
            $user->id,
            null,
            [
                'email' => $user->email,
                'user_type' => $user->user_type,
                'invitation_id' => $invitation->id,
                'staff_id' => $staff->id,
            ],
            'Invitation registration',
            'success',
            $user->id,
            $request,
        );

        $this->notifyInviterOfSignup($invitation->fresh(), $staff->fresh(), $user);

        return $user->fresh(['staff']);
    }

    /**
     * Only the user who sent the invite (typically HR) gets signup confirmation.
     */
    private function notifyInviterOfSignup(ErpRegistrationInvitation $invitation, Staff $staff, User $newUser): void
    {
        $inviterId = (int) ($invitation->invited_by ?? 0);
        if ($inviterId <= 0) {
            return;
        }

        $inviter = User::query()->where('id', $inviterId)->where('is_active', 1)->first();
        if (! $inviter) {
            return;
        }

        $name = $staff->fullName() ?: $newUser->email;

        $actionUrl = $invitation->sent_via_module === 'ict'
            ? route('ict.registration-invites.index')
            : route('hr.staff.show', $staff);

        try {
            app(PlatformNotificationService::class)->notifyUser(
                $inviter->id,
                'Invitee signed up for TICH ERP',
                "{$name} ({$newUser->email}) completed registration using your invitation.",
                'erp_registration_invitations',
                (string) $invitation->id,
                'normal',
                $actionUrl,
            );
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Failed to notify inviter of signup', [
                'invitation_id' => $invitation->id,
                'inviter_id' => $inviterId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, ErpRegistrationInvitation>
     */
    public function recentInvitations(int $limit = 10)
    {
        return ErpRegistrationInvitation::query()
            ->with(['staff.department', 'inviter'])
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    private function findStaffByPersonalEmail(string $email): ?Staff
    {
        return Staff::query()
            ->with(['department'])
            ->whereRaw('LOWER(primary_email) = ?', [strtolower(trim($email))])
            ->first();
    }
}
