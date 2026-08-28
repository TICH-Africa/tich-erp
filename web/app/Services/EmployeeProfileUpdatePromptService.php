<?php

namespace App\Services;

use App\Mail\ProfileUpdatePromptMail;
use App\Models\Staff;
use App\Models\StaffProfileUpdatePrompt;
use App\Models\User;
use App\Support\ModuleMail;
use Illuminate\Support\Str;
use InvalidArgumentException;

class EmployeeProfileUpdatePromptService
{
    public function __construct(
        protected AuditService $auditService,
    ) {}

    /**
     * @param  list<string>  $fields
     */
    public function send(Staff $staff, User $requester, array $fields, ?string $notes, string $module): StaffProfileUpdatePrompt
    {
        $fields = $this->normalizeFields($fields);

        if ($fields === []) {
            throw new InvalidArgumentException('Select at least one profile item for the employee to update.');
        }

        $email = $this->resolveRecipientEmail($staff);

        if ($email === null) {
            throw new InvalidArgumentException('This employee has no personal or organisation email on record.');
        }

        if (! $staff->user_id) {
            throw new InvalidArgumentException('This employee has not registered on the ERP yet. Send a registration invite first.');
        }

        StaffProfileUpdatePrompt::query()
            ->where('staff_id', $staff->id)
            ->where('status', StaffProfileUpdatePrompt::STATUS_PENDING)
            ->update(['status' => StaffProfileUpdatePrompt::STATUS_CANCELLED]);

        $prompt = StaffProfileUpdatePrompt::create([
            'staff_id' => $staff->id,
            'requested_by_user_id' => $requester->id,
            'requested_via_module' => $module,
            'requested_fields' => $fields,
            'notes' => $notes ? trim($notes) : null,
            'token' => Str::random(48),
            'status' => StaffProfileUpdatePrompt::STATUS_PENDING,
            'expires_at' => now()->addDays(30),
        ]);

        $mailModule = $module === 'ict' ? ModuleMail::ICT : ModuleMail::HR;
        $result = ModuleMail::trySend($mailModule, $email, new ProfileUpdatePromptMail($prompt, $staff));

        if (! $result['sent']) {
            $prompt->delete();
            throw new InvalidArgumentException($result['error'] ?? 'Could not send the profile update email.');
        }

        $prompt->update(['emailed_at' => now()]);

        $this->auditService->log(
            'staff.profile.update_prompt_sent',
            'staff_profile_update_prompts',
            $prompt->id,
            null,
            ['fields' => $fields, 'module' => $module],
            'Profile update request emailed to employee',
            'success',
            $requester->id,
        );

        return $prompt;
    }

    public function findActiveForStaff(Staff $staff, ?string $token = null): ?StaffProfileUpdatePrompt
    {
        $query = StaffProfileUpdatePrompt::query()
            ->where('staff_id', $staff->id)
            ->where('status', StaffProfileUpdatePrompt::STATUS_PENDING)
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            });

        if ($token) {
            $query->where('token', $token);
        } else {
            $query->orderByDesc('created_at');
        }

        return $query->first();
    }

    public function fulfillForStaff(Staff $staff): void
    {
        StaffProfileUpdatePrompt::query()
            ->where('staff_id', $staff->id)
            ->where('status', StaffProfileUpdatePrompt::STATUS_PENDING)
            ->update([
                'status' => StaffProfileUpdatePrompt::STATUS_FULFILLED,
                'fulfilled_at' => now(),
            ]);
    }

    public function findStaffByEmail(string $email): ?Staff
    {
        $normalized = strtolower(trim($email));

        return Staff::query()
            ->where(function ($q) use ($normalized, $email) {
                $q->whereRaw('LOWER(primary_email) = ?', [$normalized])
                    ->orWhereRaw('LOWER(organisation_email) = ?', [$normalized]);
            })
            ->orWhereHas('user', fn ($q) => $q->whereRaw('LOWER(email) = ?', [$normalized]))
            ->first();
    }

    /**
     * @param  list<string>  $fields
     * @return list<string>
     */
    private function normalizeFields(array $fields): array
    {
        $allowed = EmployeeProfileCompletenessService::requestableFieldKeys();

        return collect($fields)
            ->filter(fn ($field) => in_array($field, $allowed, true))
            ->unique()
            ->values()
            ->all();
    }

    private function resolveRecipientEmail(Staff $staff): ?string
    {
        foreach ([$staff->primary_email, $staff->organisation_email, $staff->user?->email] as $candidate) {
            if (is_string($candidate) && filter_var(trim($candidate), FILTER_VALIDATE_EMAIL)) {
                return trim($candidate);
            }
        }

        return null;
    }
}
