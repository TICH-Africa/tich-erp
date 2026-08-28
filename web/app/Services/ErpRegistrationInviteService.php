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

        $delivery = ModuleMail::trySend(
            $mailModule,
            $email,
            new ErpRegistrationInvitationEmail($invitation, $staff),
        );

        if (! $delivery['sent']) {
            $invitation->delete();

            return [
                'success' => false,
                'message' => $delivery['error'] ?? 'Could not send invitation email. Check mail settings and try again.',
            ];
        }

        app(AuditService::class)->log(
            'auth.registration_invite.sent',
            'erp_registration_invitations',
            $invitation->id,
            null,
            [
                'email' => $email,
                'staff_id' => $staff?->id,
                'mail_module' => $mailModule,
            ],
            null,
            'success',
            $invitedBy->id,
        );

        $message = $staff
            ? "Registration invitation sent to {$email} for {$staff->fullName()}."
            : "Registration invitation sent to {$email}.";

        return [
            'success' => true,
            'message' => $message,
            'invitation' => $invitation,
        ];
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

        // Invited accounts are employees — ensure a staff record exists so they can open My Employee Portal.
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
        if ($staff->organisation_email) {
            $staff->update(['organisation_email' => null]);
        }

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

        return $user->fresh(['staff']);
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
            ->with('department')
            ->whereRaw('LOWER(primary_email) = ?', [strtolower(trim($email))])
            ->first();
    }
}
