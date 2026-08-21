<?php

namespace App\Services;

use App\Events\HrSidebarCountsUpdated;
use App\Models\Feedback;
use App\Models\Grievance;
use App\Models\LeaveRequest;
use App\Models\OffboardingRequest;
use App\Models\PolicyAcknowledgement;
use App\Models\RecruitmentApplication;
use App\Models\StaffContract;
use App\Models\StaffDocument;
use App\Models\StaffOnboarding;
use App\Models\StaffAttendance;
use App\Models\StaffProfileChangeRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class HrSidebarNotificationService
{
    public const CACHE_KEY = 'hr.sidebar.counts';

    public const CACHE_TTL_SECONDS = 30;

    /** @var array<string, string> */
    public const MENU_KEYS = [
        'onboarding' => 'Onboarding',
        'recruitment' => 'Recruitment',
        'leave' => 'Leave',
        'leave.requests' => 'Leave requests',
        'documents' => 'Staff Documents',
        'offboarding' => 'Offboarding',
        'contracts' => 'Contracts',
        'profile-changes' => 'Profile changes',
        'attendance' => 'Attendance reviews',
        'policies' => 'HR Policies',
        'grievances' => 'Grievances',
        'feedback' => 'Feedback',
        'employee-relations' => 'Employee Relations',
    ];

    public function counts(bool $fresh = false): array
    {
        if ($fresh) {
            return $this->computeCounts();
        }

        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL_SECONDS, fn () => $this->computeCounts());
    }

    public function formattedCounts(bool $fresh = false): array
    {
        return collect($this->counts($fresh))
            ->mapWithKeys(fn (int $count, string $key) => [$key => $this->formatCount($count)])
            ->all();
    }

    public function formatCount(int $count): ?string
    {
        if ($count <= 0) {
            return null;
        }

        return $count > 99 ? '99+' : (string) $count;
    }

    public function broadcastCounts(): void
    {
        Cache::forget(self::CACHE_KEY);

        $counts = $this->counts(true);

        broadcast(new HrSidebarCountsUpdated(
            $counts,
            collect($counts)->map(fn (int $count) => $this->formatCount($count))->all()
        ));
    }

    private function computeCounts(): array
    {
        $leaveRequests = LeaveRequest::query()
            ->where('overall_status', 'pending_hr')
            ->where('is_cancelled', false)
            ->count();

        $grievances = $this->openGrievancesCount();
        $feedback = $this->openFeedbackCount();

        return [
            'onboarding' => StaffOnboarding::query()->where('status', 'pending_hr_review')->count(),
            'recruitment' => RecruitmentApplication::query()
                ->whereIn('status', ['submitted', 'under_review'])
                ->count(),
            'leave.requests' => $leaveRequests,
            'leave' => $leaveRequests,
            'documents' => $this->pendingDocumentsCount(),
            'offboarding' => $this->pendingOffboardingCount(),
            'contracts' => $this->contractsNeedingAction(),
            'profile-changes' => $this->pendingProfileChangesCount(),
            'attendance' => $this->pendingAttendanceCount(),
            'policies' => $this->pendingPolicyAcknowledgementsCount(),
            'grievances' => $grievances,
            'feedback' => $feedback,
            'employee-relations' => $grievances + $feedback,
        ];
    }

    private function pendingPolicyAcknowledgementsCount(): int
    {
        if (! Schema::hasTable('policy_acknowledgements')) {
            return 0;
        }

        return PolicyAcknowledgement::query()->where('is_acknowledged', false)->count();
    }

    private function openGrievancesCount(): int
    {
        if (! Schema::hasTable('grievances')) {
            return 0;
        }

        return Grievance::query()->whereIn('status', ['open', 'under_review'])->count();
    }

    private function openFeedbackCount(): int
    {
        if (! Schema::hasTable('feedback')) {
            return 0;
        }

        return Feedback::query()->whereIn('status', ['open', 'under_review'])->count();
    }

    private function contractsNeedingAction(): int
    {
        $expiring = StaffContract::query()
            ->active()
            ->expiringSoon(30)
            ->where('renewal_status', 'pending')
            ->count();

        $unsigned = StaffContract::query()
            ->active()
            ->where('is_signed', 0)
            ->count();

        return $expiring + $unsigned;
    }

    private function pendingDocumentsCount(): int
    {
        if (! Schema::hasTable('staff_documents')) {
            return 0;
        }

        if (Schema::hasColumn('staff_documents', 'status')) {
            return StaffDocument::query()->where('status', 'pending')->count();
        }

        return StaffDocument::query()
            ->where('is_verified', 0)
            ->where('is_missing', 0)
            ->whereNotNull('file_path')
            ->count();
    }

    private function pendingOffboardingCount(): int
    {
        if (! Schema::hasTable('offboarding_requests')) {
            return 0;
        }

        return OffboardingRequest::query()->where('status', 'pending')->count();
    }

    private function pendingProfileChangesCount(): int
    {
        if (! Schema::hasTable('staff_profile_change_requests')) {
            return 0;
        }

        return StaffProfileChangeRequest::query()
            ->where('status', StaffProfileChangeRequest::STATUS_PENDING)
            ->count();
    }

    private function pendingAttendanceCount(): int
    {
        if (! Schema::hasTable('staff_attendance')) {
            return 0;
        }

        return StaffAttendance::query()
            ->where('hr_review_status', StaffAttendance::HR_STATUS_PENDING)
            ->whereNotNull('clock_in_time')
            ->whereNull('clock_out_time')
            ->count();
    }
}
