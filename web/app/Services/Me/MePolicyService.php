<?php

namespace App\Services\Me;

use App\Models\Department;
use App\Models\Me\MePolicy;
use App\Models\Me\MePolicySignoff;
use App\Models\Staff;
use App\Models\User;
use App\Services\StaffPortalService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class MePolicyService
{
    public function __construct(
        protected StaffPortalService $staffPortal,
    ) {}

    public function currentPublishedPolicy(?string $fiscalYear = null): ?MePolicy
    {
        $query = MePolicy::query()->published()->orderByDesc('published_at')->orderByDesc('id');

        if ($fiscalYear) {
            $query->where('fiscal_year', $fiscalYear);
        }

        return $query->first();
    }

    /**
     * @param  array{fiscal_year: string, title: string, version?: ?string, description?: ?string, effective_date?: ?string}  $data
     */
    public function uploadPolicy(array $data, UploadedFile $file, User $user): MePolicy
    {
        $staff = $this->staffPortal->staffForUser($user);
        $path = $file->store('me-policies/'.date('Y'), 'public');

        return MePolicy::query()->create([
            'fiscal_year' => $data['fiscal_year'],
            'title' => $data['title'],
            'version' => $data['version'] ?? null,
            'file_path' => $path,
            'description' => $data['description'] ?? null,
            'effective_date' => $data['effective_date'] ?? null,
            'status' => 'draft',
            'uploaded_by' => $staff?->id,
            'uploaded_at' => now(),
        ]);
    }

    public function publish(MePolicy $policy): MePolicy
    {
        // Archive other published policies for the same fiscal year.
        MePolicy::query()
            ->where('fiscal_year', $policy->fiscal_year)
            ->where('status', 'published')
            ->where('id', '!=', $policy->id)
            ->update(['status' => 'archived']);

        $policy->update([
            'status' => 'published',
            'published_at' => now(),
        ]);

        return $policy->fresh();
    }

    public function staffIsHod(Staff $staff, ?Department $department = null): bool
    {
        $department ??= $staff->department;

        if (! $department) {
            return false;
        }

        return (int) $department->hod_id === (int) $staff->id;
    }

    public function departmentHasHodSignoff(MePolicy $policy, Department $department): bool
    {
        if (! $department->hod_id) {
            return false;
        }

        return MePolicySignoff::query()
            ->where('policy_id', $policy->id)
            ->where('department_id', $department->id)
            ->where('staff_id', $department->hod_id)
            ->exists();
    }

    public function userMaySubmitBudget(User $user, Department $department): bool
    {
        $policy = $this->currentPublishedPolicy();
        if (! $policy) {
            // No published policy yet - do not block budgeting.
            return true;
        }

        return $this->departmentHasHodSignoff($policy, $department);
    }

    public function gateMessage(Department $department): string
    {
        $policy = $this->currentPublishedPolicy();
        $title = $policy?->title ?? 'Standard M&E Policy';

        return "Heads of Department must view and digitally sign \"{$title}\" before submitting annual budgets and departmental plans. Sign off from the M&E policy portal, then try again.";
    }

    /**
     * @param  array{signed_name: string, employee_number?: ?string, signature?: ?string}  $data
     */
    public function signOff(MePolicy $policy, User $user, array $data, ?string $ip = null): MePolicySignoff
    {
        if (! $policy->isPublished()) {
            throw new \RuntimeException('Only the published M&E policy can be signed.');
        }

        $staff = $this->staffPortal->staffForUser($user);
        if (! $staff) {
            throw new \RuntimeException('Your account is not linked to a staff record.');
        }

        $department = $staff->department;
        if (! $department) {
            throw new \RuntimeException('Your staff profile has no department assignment.');
        }

        if (! $this->staffIsHod($staff, $department) && ! $user->hasAnyRole(['Super Admin', 'CEO', 'Monitoring and Evaluation Officer'])) {
            // Allow HOD only for the gate; officers may sign for testing/admin depts if they are HOD.
            if ((int) ($department->hod_id ?? 0) !== (int) $staff->id) {
                throw new \RuntimeException('Only the Head of Department can digitally sign the M&E policy for this department.');
            }
        }

        return MePolicySignoff::query()->updateOrCreate(
            [
                'policy_id' => $policy->id,
                'department_id' => $department->id,
                'staff_id' => $staff->id,
            ],
            [
                'user_id' => $user->id,
                'signed_name' => $data['signed_name'],
                'employee_number' => $data['employee_number'] ?? $staff->employee_number,
                'signature' => $data['signature'] ?? $data['signed_name'],
                'ip_address' => $ip,
                'signed_at' => now(),
            ]
        );
    }

    public function signoffProgress(MePolicy $policy): array
    {
        $departments = Department::query()
            ->whereNotNull('hod_id')
            ->orderBy('dept_name')
            ->get(['id', 'dept_name', 'dept_code', 'hod_id']);

        $signedIds = MePolicySignoff::query()
            ->where('policy_id', $policy->id)
            ->pluck('department_id')
            ->all();

        $signedSet = array_fill_keys($signedIds, true);

        return [
            'total' => $departments->count(),
            'signed' => count(array_intersect($departments->pluck('id')->all(), $signedIds)),
            'departments' => $departments->map(fn (Department $d) => [
                'id' => $d->id,
                'name' => $d->dept_name,
                'code' => $d->dept_code,
                'signed' => isset($signedSet[$d->id]),
            ]),
        ];
    }

    public function fileUrl(MePolicy $policy): string
    {
        return Storage::disk('public')->url($policy->file_path);
    }
}
