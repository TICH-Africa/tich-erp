<?php

namespace App\Services;

use App\Models\Applicant;
use App\Models\Campus;
use App\Models\RecruitmentApplication;
use App\Models\Staff;
use App\Models\StaffAllowance;
use App\Models\StaffDocument;
use App\Models\StaffNextOfKin;
use App\Models\StaffOnboarding;
use App\Models\StaffStatusHistory;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class StaffLifecycleService
{
    public function __construct(
        protected AuditService $auditService,
        protected EncryptionService $encryptionService,
        protected PlatformNotificationService $notificationService,
    ) {}

    public function convertApplicantToEmployee(RecruitmentApplication $application, array $employmentDetails, int $convertedBy): Staff
    {
        if ($application->is_onboarded) {
            throw new \InvalidArgumentException('Applicant has already been converted to employee');
        }

        if (! $application->offer_accepted) {
            throw new \InvalidArgumentException('Offer has not been accepted');
        }

        return DB::transaction(function () use ($application, $employmentDetails, $convertedBy) {
            $employeeNumber = $this->generateEmployeeNumber();
            $employmentDetails = $this->normalizeEmploymentEmails($employmentDetails, $application);

            $staff = Staff::create(array_merge($employmentDetails, [
                'employee_number' => $employeeNumber,
                'employment_status' => 'onboarding',
                'is_on_probation' => $employmentDetails['employment_category'] === 'permanent' ? 0 : 1,
                'employment_start_date' => $employmentDetails['employment_start_date'] ?? now()->toDateString(),
                'created_by' => $convertedBy,
            ]));

            $onboarding = StaffOnboarding::create([
                'staff_id' => $staff->id,
                'applicant_id' => $application->id,
                'onboarding_number' => 'ONB-' . strtoupper(Str::random(8)),
                'current_step' => 'biodata',
                'status' => 'in_progress',
                'completed_steps' => ['applicant_converted'],
            ]);

            $application->update([
                'new_staff_id' => $staff->id,
                'is_onboarded' => 1,
            ]);

            $this->auditService->log(
                'staff.onboarding.started',
                'staff',
                $staff->id,
                null,
                [
                    'employee_number' => $employeeNumber,
                    'applicant_id' => $application->id,
                    'employment_category' => $employmentDetails['employment_category'] ?? null,
                ],
                'Applicant converted to employee',
                'success',
                $convertedBy
            );

            return $staff;
        });
    }

    public function updateOnboardingStep(int $staffId, string $step, array $data, int $updatedBy): StaffOnboarding
    {
        $onboarding = StaffOnboarding::where('staff_id', $staffId)
            ->where('status', 'in_progress')
            ->latest()
            ->firstOrFail();

        $completedSteps = $onboarding->completed_steps ?? [];
        if (! in_array($step, $completedSteps, true)) {
            $completedSteps[] = $step;
        }

        $onboarding->update([
            'current_step' => $step,
            'completed_steps' => $completedSteps,
            ...$data,
        ]);

        $this->auditService->log(
            'staff.onboarding.step_updated',
            'staff_onboarding',
            $onboarding->id,
            ['current_step' => $onboarding->getOriginal('current_step')],
            ['current_step' => $step],
            'Onboarding step updated',
            'success',
            $updatedBy
        );

        return $onboarding->fresh();
    }

    public function completeOnboarding(int $staffId, int $completedBy): StaffOnboarding
    {
        $onboarding = StaffOnboarding::where('staff_id', $staffId)
            ->where('status', '!=', 'completed')
            ->latest()
            ->firstOrFail();

        $requiredSteps = ['biodata', 'employment_terms', 'banking', 'documents', 'contract', 'orientation', 'statutory', 'ess_account'];
        $completedSteps = $onboarding->completed_steps ?? [];

        $missingSteps = array_diff($requiredSteps, $completedSteps);
        if (! empty($missingSteps)) {
            throw new \InvalidArgumentException('Cannot complete onboarding. Missing steps: ' . implode(', ', $missingSteps));
        }

        DB::transaction(function () use ($onboarding, $completedBy) {
            $onboarding->update([
                'current_step' => 'completed',
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            $onboarding->staff->update([
                'employment_status' => 'active',
                'is_profile_locked' => 1,
                'onboarding_completed_at' => now(),
            ]);

            $this->auditService->log(
                'staff.onboarding.completed',
                'staff',
                $onboarding->staff_id,
                ['employment_status' => 'onboarding'],
                ['employment_status' => 'active', 'is_profile_locked' => 1],
                'Onboarding completed',
                'success',
                $completedBy
            );
        });

        return $onboarding->fresh();
    }

    public function lockProfile(int $staffId, int $lockedBy): Staff
    {
        $staff = Staff::findOrFail($staffId);

        if ($staff->is_profile_locked) {
            throw new \InvalidArgumentException('Profile is already locked');
        }

        $staff->update([
            'is_profile_locked' => 1,
        ]);

        $this->auditService->log(
            'staff.profile.locked',
            'staff',
            $staffId,
            ['is_profile_locked' => 0],
            ['is_profile_locked' => 1],
            'Profile locked by HR',
            'success',
            $lockedBy
        );

        return $staff->fresh();
    }

    public function requestProfileChange(int $staffId, array $changes, string $reason, int $requestedBy): void
    {
        $staff = Staff::findOrFail($staffId);

        if (! $staff->is_profile_locked) {
            throw new \InvalidArgumentException('Profile is not locked. Edit directly instead.');
        }

        $allowedFields = [
            'phone_number', 'alt_phone_number', 'postal_address', 'postal_code', 'physical_address',
            'home_county', 'primary_email', 'emergency_contact_name', 'emergency_contact_phone',
            'emergency_contact_relationship',
        ];

        $invalidFields = array_diff(array_keys($changes), $allowedFields);
        if (! empty($invalidFields)) {
            throw new \InvalidArgumentException('Cannot request change for locked fields: ' . implode(', ', $invalidFields));
        }

        $this->auditService->log(
            'staff.profile.change_requested',
            'staff',
            $staffId,
            null,
            [
                'requested_fields' => array_keys($changes),
                'reason' => $reason,
                'requested_by' => $requestedBy,
            ],
            $reason,
            'pending',
            $requestedBy
        );
    }

    public function approveProfileChange(int $staffId, array $approvedChanges, int $approvedBy): Staff
    {
        $staff = Staff::findOrFail($staffId);

        if (! $staff->is_profile_locked) {
            throw new \InvalidArgumentException('Profile is not locked');
        }

        $allowedFields = [
            'phone_number', 'alt_phone_number', 'postal_address', 'postal_code', 'physical_address',
            'home_county', 'primary_email', 'emergency_contact_name', 'emergency_contact_phone',
            'emergency_contact_relationship', 'photo_path',
        ];

        $invalidFields = array_diff(array_keys($approvedChanges), $allowedFields);
        if (! empty($invalidFields)) {
            throw new \InvalidArgumentException('Cannot approve change for fields: ' . implode(', ', $invalidFields));
        }

        $oldValues = [];
        $newValues = [];

        foreach ($approvedChanges as $field => $value) {
            $oldValues[$field] = $staff->{$field};
            $newValues[$field] = $value;
        }

        $staff->update($approvedChanges);

        $this->auditService->log(
            'staff.profile.change_approved',
            'staff',
            $staffId,
            $oldValues,
            $newValues,
            'Profile change approved by HR',
            'success',
            $approvedBy
        );

        return $staff->fresh();
    }

    public function recordStatusChange(int $staffId, string $changeType, ?string $previousStatus, string $newStatus, array $metadata, ?int $approvedBy, ?string $approvalReference, ?string $effectiveDate): StaffStatusHistory
    {
        $staff = Staff::findOrFail($staffId);

        $history = StaffStatusHistory::create([
            'staff_id' => $staffId,
            'change_type' => $changeType,
            'previous_status' => $previousStatus,
            'new_status' => $newStatus,
            'metadata' => $metadata,
            'approved_by' => $approvedBy,
            'approval_reference' => $approvalReference,
            'effective_date' => $effectiveDate ?: now()->toDateString(),
        ]);

        if (in_array($changeType, ['resignation', 'retirement', 'termination', 'redundancy', 'dismissal', 'death'], true)) {
            $staff->update(['employment_status' => $newStatus]);
        }

        $this->auditService->log(
            'staff.status.changed',
            'staff_status_history',
            $history->id,
            ['previous_status' => $previousStatus],
            ['new_status' => $newStatus, 'change_type' => $changeType],
            "Status changed to {$newStatus}",
            'success',
            $approvedBy
        );

        return $history;
    }

    public function addDocument(int $staffId, array $documentData, int $uploadedBy): StaffDocument
    {
        $staff = Staff::findOrFail($staffId);

        $document = StaffDocument::create(array_merge($documentData, [
            'staff_id' => $staffId,
            'version' => '1',
            'is_missing' => 0,
        ]));

        $this->auditService->log(
            'staff.document.uploaded',
            'staff_documents',
            $document->id,
            null,
            ['document_type' => $documentData['document_type']],
            'Document uploaded',
            'success',
            $uploadedBy
        );

        return $document;
    }

    public function addAllowance(int $staffId, array $allowanceData, int $createdBy): StaffAllowance
    {
        $staff = Staff::findOrFail($staffId);

        $allowance = StaffAllowance::create(array_merge($allowanceData, [
            'staff_id' => $staffId,
        ]));

        $this->auditService->log(
            'staff.allowance.created',
            'staff_allowances',
            $allowance->id,
            null,
            ['allowance_type' => $allowanceData['allowance_type'], 'amount' => $allowanceData['amount']],
            'Allowance added',
            'success',
            $createdBy
        );

        return $allowance;
    }

    /**
     * @param  array<string, mixed>  $employmentDetails
     * @return array<string, mixed>
     */
    private function normalizeEmploymentEmails(array $employmentDetails, RecruitmentApplication $application): array
    {
        unset($employmentDetails['email']);

        if (empty($employmentDetails['primary_email'])) {
            $employmentDetails['primary_email'] = $application->email;
        }

        if (array_key_exists('organisation_email', $employmentDetails) && $employmentDetails['organisation_email'] === '') {
            $employmentDetails['organisation_email'] = null;
        }

        // Organisation email is assigned manually by ICT, not during HR onboarding conversion.
        unset($employmentDetails['organisation_email']);

        return $employmentDetails;
    }

    public function generateEmployeeNumber(): string
    {
        $year = now()->year;
        $prefix = "EMP/{$year}/";

        $last = Staff::where('employee_number', 'like', $prefix . '%')
            ->orderByDesc('employee_number')
            ->value('employee_number');

        if ($last) {
            $num = (int) str_replace($prefix, '', $last);
            $num++;
        } else {
            $num = 1;
        }

        return $prefix . str_pad((string) $num, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Create a provisional staff row for an ERP invitee.
     * Names are placeholders (employee enters their own). No @tich.africa org email is issued.
     */
    public function createProvisionalInviteStaff(string $personalEmail, ?int $createdBy = null, ?User $linkUser = null): Staff
    {
        return DB::transaction(function () use ($personalEmail, $createdBy, $linkUser) {
            $staff = Staff::query()->create([
                'employee_number' => $this->generateEmployeeNumber(),
                // Placeholders only - completeness treats these as incomplete.
                'first_name' => 'Pending',
                'surname' => 'Invitee',
                'date_of_birth' => '1990-01-01',
                'gender' => 'Unspecified',
                'primary_email' => strtolower(trim($personalEmail)),
                'organisation_email' => null,
                'phone_number' => '0700000000',
                'department_id' => null,
                'job_title' => 'Pending assignment',
                'employment_category' => 'contract',
                'payroll_scheme' => 'employee',
                'employment_start_date' => now()->toDateString(),
                'employment_status' => 'onboarding',
                'is_profile_locked' => false,
                'gross_monthly_salary' => 0,
                'created_by' => $createdBy,
                'user_id' => $linkUser?->id,
            ]);

            $this->ensureOnboardingRecord($staff);

            if ($linkUser && ! $linkUser->staff_id) {
                $linkUser->forceFill(['staff_id' => $staff->id])->save();
            }

            return $staff;
        });
    }

    /**
     * Ensure invited/provisional staff appear in HR onboarding + are User↔Staff linked.
     */
    public function ensureEmployeeIdentity(Staff $staff, ?User $user = null): Staff
    {
        $this->ensureOnboardingRecord($staff);

        $user ??= $staff->user_id ? User::query()->find($staff->user_id) : null;

        if ($user) {
            $staffUpdates = [];
            if ((int) $staff->user_id !== (int) $user->id) {
                $staffUpdates['user_id'] = $user->id;
            }
            if ($staffUpdates !== []) {
                $staff->update($staffUpdates);
            }

            if ((int) $user->staff_id !== (int) $staff->id) {
                $user->forceFill(['staff_id' => $staff->id])->save();
            }

            if ($user->user_type !== 'staff' && $user->user_type !== 'admin' && $user->user_type !== 'super_admin') {
                $user->forceFill(['user_type' => 'staff'])->save();
            }
        }

        // Never invent @tich.africa here - HR issues organisation email deliberately.
        if ($user) {
            app(RBACService::class)->reconcileStaffEmploymentDepartment($user);
        }

        return $staff->fresh(['user', 'onboarding']);
    }

    public function ensureOnboardingRecord(Staff $staff): StaffOnboarding
    {
        $existing = StaffOnboarding::query()->where('staff_id', $staff->id)->first();

        if ($existing) {
            return $existing;
        }

        return StaffOnboarding::query()->create([
            'staff_id' => $staff->id,
            'onboarding_number' => 'ONB-'.strtoupper(Str::random(8)),
            'current_step' => 'biodata',
            'status' => 'in_progress',
            'completed_steps' => ['invite_registered'],
        ]);
    }
}
