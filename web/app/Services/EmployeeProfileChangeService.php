<?php

namespace App\Services;

use App\Models\Staff;
use App\Models\StaffProfileChangeRequest;
use App\Models\StaffQualification;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;

class EmployeeProfileChangeService
{
    /** @var list<string> */
    public const EDITABLE_FIELDS = [
        'first_name',
        'middle_name',
        'surname',
        'date_of_birth',
        'gender',
        'primary_email',
        'phone_number',
        'alt_phone_number',
        'marital_status',
        'postal_address',
        'postal_code',
        'physical_address',
        'home_county',
        'emergency_contact_name',
        'emergency_contact_phone',
        'emergency_contact_relationship',
    ];

    /** Fields applied immediately during first profile completion (invitees). */
    public const INITIAL_COMPLETION_FIELDS = self::EDITABLE_FIELDS;

    public function __construct(
        protected AuditService $auditService,
        protected PlatformNotificationService $notifications,
        protected StoredFileService $files,
    ) {}

    /**
     * @param  array<string, mixed>  $input
     * @return list<StaffProfileChangeRequest>
     */
    public function submitUpdates(Staff $staff, User $user, array $input): array
    {
        $created = [];

        $fieldChanges = $this->extractFieldChanges($staff, $input);
        if ($fieldChanges !== []) {
            $created[] = $this->createRequest(
                $staff,
                $user,
                StaffProfileChangeRequest::TYPE_PROFILE_UPDATE,
                $this->snapshotFields($staff, array_keys($fieldChanges)),
                $fieldChanges,
                null,
                $input['employee_notes'] ?? null,
            );
        }

        if (! empty($input['cropped_photo'])) {
            $path = $this->storeCroppedPhoto($staff, (string) $input['cropped_photo']);
            $created[] = $this->createRequest(
                $staff,
                $user,
                StaffProfileChangeRequest::TYPE_PHOTO,
                ['photo_path' => $staff->photo_path],
                ['photo_path' => $path],
                $path,
                $input['employee_notes'] ?? null,
            );
        }

        if (! empty($input['qualification_type']) && ! empty($input['qualification_name'])) {
            $attachmentPath = null;
            if ($input['certificate_file'] instanceof UploadedFile) {
                $attachmentPath = $this->storeQualificationFile($staff, $input['certificate_file']);
            }

            $proposed = [
                'qualification_type' => $input['qualification_type'],
                'qualification_name' => $input['qualification_name'],
                'institution' => $input['institution'] ?? '',
                'country' => $input['country'] ?? 'Kenya',
                'year_completed' => (int) ($input['year_completed'] ?? now()->year),
                'grade_or_class' => $input['grade_or_class'] ?? null,
                'certificate_number' => $input['certificate_number'] ?? null,
                'document_path' => $attachmentPath,
            ];

            $created[] = $this->createRequest(
                $staff,
                $user,
                StaffProfileChangeRequest::TYPE_QUALIFICATION,
                null,
                $proposed,
                $attachmentPath,
                $input['employee_notes'] ?? null,
            );
        }

        if ($created === []) {
            throw new InvalidArgumentException('No changes were detected. Update at least one field, photo, or qualification.');
        }

        $this->notifyHrPending($staff, count($created));

        app(HrSidebarNotificationService::class)->broadcastCounts();

        return $created;
    }

    /**
     * First-time / incomplete profile: apply contact details immediately so the employee
     * is not blocked waiting for HR approval.
     *
     * @param  array<string, mixed>  $input
     */
    public function applySelfServiceCompletion(Staff $staff, User $user, array $input): Staff
    {
        $updates = [];

        foreach (self::INITIAL_COMPLETION_FIELDS as $field) {
            if (! array_key_exists($field, $input)) {
                continue;
            }

            $value = $input[$field];
            $value = is_string($value) ? trim($value) : $value;
            $updates[$field] = $value === '' ? null : $value;
        }

        if (! empty($input['cropped_photo'])) {
            $updates['photo_path'] = $this->storeCroppedPhoto($staff, (string) $input['cropped_photo']);
        }

        if ($updates === []) {
            throw new InvalidArgumentException('Fill in the required profile fields to continue.');
        }

        $before = $staff->only(array_keys($updates));

        DB::transaction(function () use ($staff, $user, $updates) {
            $staff->update($updates);
            app(StaffLifecycleService::class)->ensureEmployeeIdentity($staff, $user);
        });

        $this->auditService->log(
            'staff.profile.self_service_completed',
            'staff',
            $staff->id,
            $before,
            $updates,
            'Employee completed required profile details for ERP access',
            'success',
            $user->id,
        );

        $this->notifyHrSelfServiceCompletion($staff->fresh());

        return $staff->fresh();
    }

    public function approve(StaffProfileChangeRequest $request, Staff $reviewer, ?string $hrNotes = null): StaffProfileChangeRequest
    {
        if (! $request->isPending()) {
            throw new InvalidArgumentException('This request has already been reviewed.');
        }

        return DB::transaction(function () use ($request, $reviewer, $hrNotes) {
            $staff = $request->staff()->lockForUpdate()->firstOrFail();

            match ($request->request_type) {
                StaffProfileChangeRequest::TYPE_PHOTO => $this->applyPhotoChange($staff, $request),
                StaffProfileChangeRequest::TYPE_QUALIFICATION => $this->applyQualificationChange($staff, $request, $reviewer),
                default => $staff->update($request->proposed_changes ?? []),
            };

            $requestedBy = $request->requestedBy;
            app(StaffLifecycleService::class)->ensureEmployeeIdentity(
                $staff->fresh(),
                $requestedBy instanceof User ? $requestedBy : null,
            );

            $request->update([
                'status' => StaffProfileChangeRequest::STATUS_APPROVED,
                'reviewed_by_staff_id' => $reviewer->id,
                'reviewed_at' => now(),
                'hr_notes' => $hrNotes,
            ]);

            $this->auditService->log(
                'staff.profile.change_approved',
                'staff_profile_change_requests',
                $request->id,
                $request->current_snapshot,
                $request->proposed_changes,
                'Profile change approved by HR',
                'success',
                $reviewer->user_id,
            );

            $this->notifyEmployeeReviewed($request, approved: true);

            app(HrSidebarNotificationService::class)->broadcastCounts();

            return $request->fresh(['staff', 'requestedBy']);
        });
    }

    public function reject(StaffProfileChangeRequest $request, Staff $reviewer, string $reason, ?string $hrNotes = null): StaffProfileChangeRequest
    {
        if (! $request->isPending()) {
            throw new InvalidArgumentException('This request has already been reviewed.');
        }

        $request->update([
            'status' => StaffProfileChangeRequest::STATUS_REJECTED,
            'reviewed_by_staff_id' => $reviewer->id,
            'reviewed_at' => now(),
            'rejection_reason' => $reason,
            'hr_notes' => $hrNotes,
        ]);

        if ($request->request_type === StaffProfileChangeRequest::TYPE_PHOTO && $request->attachment_path) {
            $this->files->delete($request->attachment_path, 'public');
        }

        $this->auditService->log(
            'staff.profile.change_rejected',
            'staff_profile_change_requests',
            $request->id,
            null,
            ['reason' => $reason],
            'Profile change rejected by HR',
            'warning',
            $reviewer->user_id,
        );

        $this->notifyEmployeeReviewed($request, approved: false);

        app(HrSidebarNotificationService::class)->broadcastCounts();

        return $request->fresh(['staff', 'requestedBy']);
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    private function extractFieldChanges(Staff $staff, array $input): array
    {
        $changes = [];

        foreach (self::EDITABLE_FIELDS as $field) {
            if (! array_key_exists($field, $input)) {
                continue;
            }

            $newValue = $input[$field];
            $newValue = is_string($newValue) ? trim($newValue) : $newValue;
            $newValue = $newValue === '' ? null : $newValue;

            $current = $staff->{$field};
            $current = is_string($current) ? trim((string) $current) : $current;
            $current = $current === '' ? null : $current;

            if ($newValue != $current) {
                $changes[$field] = $newValue;
            }
        }

        return $changes;
    }

    /**
     * @param  list<string>  $fields
     * @return array<string, mixed>
     */
    private function snapshotFields(Staff $staff, array $fields): array
    {
        $snapshot = [];
        foreach ($fields as $field) {
            $snapshot[$field] = $staff->{$field};
        }

        return $snapshot;
    }

    /**
     * @param  array<string, mixed>|null  $currentSnapshot
     * @param  array<string, mixed>  $proposedChanges
     */
    private function createRequest(
        Staff $staff,
        User $user,
        string $type,
        ?array $currentSnapshot,
        array $proposedChanges,
        ?string $attachmentPath,
        ?string $employeeNotes,
    ): StaffProfileChangeRequest {
        $pendingExists = StaffProfileChangeRequest::query()
            ->where('staff_id', $staff->id)
            ->where('request_type', $type)
            ->where('status', StaffProfileChangeRequest::STATUS_PENDING)
            ->exists();

        if ($pendingExists && $type !== StaffProfileChangeRequest::TYPE_QUALIFICATION) {
            throw new InvalidArgumentException('You already have a pending '.$this->typeLabel($type).' request. Wait for HR review before submitting another.');
        }

        $request = StaffProfileChangeRequest::create([
            'staff_id' => $staff->id,
            'requested_by_user_id' => $user->id,
            'request_type' => $type,
            'status' => StaffProfileChangeRequest::STATUS_PENDING,
            'current_snapshot' => $currentSnapshot,
            'proposed_changes' => $proposedChanges,
            'attachment_path' => $attachmentPath,
            'employee_notes' => $employeeNotes,
        ]);

        $this->auditService->log(
            'staff.profile.change_requested',
            'staff_profile_change_requests',
            $request->id,
            $currentSnapshot,
            $proposedChanges,
            'Employee submitted profile change for HR review',
            'pending',
            $user->id,
        );

        return $request;
    }

    private function applyPhotoChange(Staff $staff, StaffProfileChangeRequest $request): void
    {
        $newPath = $request->proposed_changes['photo_path'] ?? $request->attachment_path;

        if (! $newPath) {
            throw new InvalidArgumentException('No photo path on request.');
        }

        $finalPath = "staff/{$staff->employee_number}/photos/profile.webp";

        if ($newPath !== $finalPath && Storage::disk('public')->exists($newPath)) {
            Storage::disk('public')->makeDirectory("staff/{$staff->employee_number}/photos");
            Storage::disk('public')->move($newPath, $finalPath);
        }

        $staff->update(['photo_path' => $finalPath]);
    }

    private function applyQualificationChange(Staff $staff, StaffProfileChangeRequest $request, Staff $reviewer): void
    {
        $data = $request->proposed_changes;

        StaffQualification::create([
            'staff_id' => $staff->id,
            'qualification_type' => $data['qualification_type'],
            'qualification_name' => $data['qualification_name'],
            'institution' => $data['institution'] ?? '',
            'country' => $data['country'] ?? 'Kenya',
            'year_completed' => $data['year_completed'] ?? now()->year,
            'grade_or_class' => $data['grade_or_class'] ?? null,
            'certificate_number' => $data['certificate_number'] ?? null,
            'document_path' => $data['document_path'] ?? $request->attachment_path,
            'is_verified' => 1,
            'verified_by' => $reviewer->id,
            'verified_at' => now(),
        ]);
    }

    private function storeCroppedPhoto(Staff $staff, string $base64): string
    {
        $base64 = preg_replace('#^data:image/\w+;base64,#i', '', $base64) ?? '';
        $binary = base64_decode($base64, true);

        if ($binary === false || $binary === '') {
            throw new InvalidArgumentException('Invalid profile photo data.');
        }

        $directory = "staff/{$staff->employee_number}/photos";
        $path = $directory.'/pending_'.time().'.webp';

        return $this->files->put($binary, $path, 'public');
    }

    private function storeQualificationFile(Staff $staff, UploadedFile $file): string
    {
        return $this->files->store(
            $file,
            "staff/{$staff->employee_number}/qualifications",
            'public',
            time().'_'.$file->getClientOriginalName(),
        );
    }

    private function notifyHrPending(Staff $staff, int $count): void
    {
        $rbac = app(RBACService::class);
        $userIds = User::query()
            ->where('is_active', 1)
            ->get()
            ->filter(fn (User $user) => $rbac->hasPermission($user, 'hr.staff.view'))
            ->pluck('id')
            ->unique()
            ->values()
            ->all();

        if ($userIds === []) {
            return;
        }

        $this->notifications->notifyUsers(
            $userIds,
            'Profile change pending review',
            "{$staff->fullName()} submitted {$count} profile change request(s) for HR approval.",
            'staff_profile_change',
            (string) $staff->id,
        );
    }

    private function notifyHrSelfServiceCompletion(Staff $staff): void
    {
        $rbac = app(RBACService::class);
        $userIds = User::query()
            ->where('is_active', 1)
            ->get()
            ->filter(fn (User $user) => $rbac->hasPermission($user, 'hr.staff.view'))
            ->pluck('id')
            ->unique()
            ->values()
            ->all();

        if ($userIds === []) {
            return;
        }

        $this->notifications->notifyUsers(
            $userIds,
            'Employee profile confirmed',
            "{$staff->fullName()} confirmed their contact and emergency details in My Employee Portal.",
            'staff',
            (string) $staff->id,
        );
    }

    private function notifyEmployeeReviewed(StaffProfileChangeRequest $request, bool $approved): void
    {
        if (! $request->staff?->user_id) {
            return;
        }

        if ($approved) {
            $this->notifications->notifyUser(
                $request->staff->user_id,
                'Profile change approved',
                'HR approved your '.$request->typeLabel().' update.',
                'staff_profile_change',
                $request->id,
                'normal',
            );

            return;
        }

        $this->notifications->notifyUser(
            $request->staff->user_id,
            'Profile change rejected',
            'HR rejected your '.$request->typeLabel().' update. Reason: '.$request->rejection_reason,
            'staff_profile_change',
            $request->id,
            'normal',
        );
    }

    private function typeLabel(string $type): string
    {
        return match ($type) {
            StaffProfileChangeRequest::TYPE_PHOTO => 'profile photo',
            StaffProfileChangeRequest::TYPE_QUALIFICATION => 'qualification',
            default => 'profile details',
        };
    }
}
