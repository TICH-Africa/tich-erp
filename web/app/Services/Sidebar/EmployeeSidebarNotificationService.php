<?php

namespace App\Services\Sidebar;

use App\Models\Feedback;
use App\Models\Grievance;
use App\Models\LeaveRequest;
use App\Models\PolicyAcknowledgement;
use App\Models\Staff;
use App\Models\StaffProfileChangeRequest;
use App\Services\Sidebar\Concerns\FormatsSidebarBadgeCounts;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class EmployeeSidebarNotificationService
{
    use FormatsSidebarBadgeCounts;

    public const CACHE_KEY_PREFIX = 'employee.sidebar.counts.';

    public const CACHE_TTL_SECONDS = 30;

    /** @var array<string, string> */
    public const MENU_KEYS = [
        'profile-changes' => 'Update profile',
        'leave.returned' => 'Apply for leave',
        'concerns' => 'Concerns & issues',
        'feedback' => 'My feedback',
        'policies' => 'HR Policies',
    ];

    /**
     * @return array<string, int>
     */
    public function countsFor(Staff $staff, bool $fresh = false): array
    {
        $cacheKey = self::CACHE_KEY_PREFIX.$staff->id;

        if ($fresh) {
            return $this->computeCounts($staff);
        }

        return Cache::remember($cacheKey, self::CACHE_TTL_SECONDS, fn () => $this->computeCounts($staff));
    }

    /**
     * @return array<string, string|null>
     */
    public function formattedCountsFor(Staff $staff, bool $fresh = false): array
    {
        return $this->formattedCounts($this->countsFor($staff, $fresh));
    }

    public function forget(Staff $staff): void
    {
        Cache::forget(self::CACHE_KEY_PREFIX.$staff->id);
    }

    /**
     * @return array<string, int>
     */
    private function computeCounts(Staff $staff): array
    {
        return [
            'profile-changes' => $this->pendingProfileChanges($staff),
            'leave.returned' => $this->returnedLeaveRequests($staff),
            'concerns' => $this->openConcerns($staff),
            'feedback' => $this->openFeedback($staff),
            'policies' => $this->unacknowledgedPolicies($staff),
        ];
    }

    private function pendingProfileChanges(Staff $staff): int
    {
        if (! Schema::hasTable('staff_profile_change_requests')) {
            return 0;
        }

        return StaffProfileChangeRequest::query()
            ->where('staff_id', $staff->id)
            ->where('status', StaffProfileChangeRequest::STATUS_PENDING)
            ->count();
    }

    private function returnedLeaveRequests(Staff $staff): int
    {
        return LeaveRequest::query()
            ->where('staff_id', $staff->id)
            ->where('overall_status', 'returned')
            ->where('is_cancelled', false)
            ->count();
    }

    private function openConcerns(Staff $staff): int
    {
        if (! Schema::hasTable('grievances')) {
            return 0;
        }

        return Grievance::query()
            ->where('staff_id', $staff->id)
            ->whereIn('status', ['open', 'under_review'])
            ->count();
    }

    private function openFeedback(Staff $staff): int
    {
        if (! Schema::hasTable('feedback')) {
            return 0;
        }

        return Feedback::query()
            ->where('staff_id', $staff->id)
            ->whereIn('status', ['open', 'under_review'])
            ->count();
    }

    private function unacknowledgedPolicies(Staff $staff): int
    {
        if (! Schema::hasTable('policy_acknowledgements')) {
            return 0;
        }

        return PolicyAcknowledgement::query()
            ->where('staff_id', $staff->id)
            ->where('is_acknowledged', false)
            ->count();
    }
}
