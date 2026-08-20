<?php

namespace App\Services\Sidebar;

use App\Events\AdminSidebarCountsUpdated;
use App\Models\AcademicProgram;
use App\Models\Campus;
use App\Models\Department;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class AdminSidebarNotificationService
{
    public const CACHE_KEY = 'admin.sidebar.counts';

    public const CACHE_TTL_SECONDS = 30;

    /** @var array<string, string> */
    public const MENU_KEYS = [
        'campuses' => 'Campuses',
        'departments' => 'Departments',
        'programs' => 'Programmes & courses',
        'users' => 'Users & access',
        'audit-logs' => 'Audit logs',
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

        broadcast(new AdminSidebarCountsUpdated(
            $counts,
            collect($counts)->map(fn (int $count) => $this->formatCount($count))->all()
        ));
    }

    private function computeCounts(): array
    {
        $pendingDepartments = 0;
        if (Schema::hasTable('departments') && Schema::hasColumn('departments', 'approval_status')) {
            $pendingDepartments = Department::query()
                ->whereIn('approval_status', ['pending', 'pending_approval', 'draft'])
                ->count();
        }

        $pendingPrograms = 0;
        if (Schema::hasTable('academic_programs')) {
            $pendingPrograms = AcademicProgram::query()
                ->where('status', 'pending_ceo')
                ->count();
        }

        $inactiveCampuses = 0;
        if (Schema::hasTable('campuses') && Schema::hasColumn('campuses', 'is_active')) {
            $inactiveCampuses = Campus::query()->where('is_active', false)->count();
        }

        $inactiveUsers = 0;
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'is_active')) {
            $inactiveUsers = User::query()->where('is_active', false)->count();
        }

        $recentFailures = 0;
        if (Schema::hasTable('audit_logs') && Schema::hasColumn('audit_logs', 'status')) {
            $recentFailures = \App\Models\AuditLog::query()
                ->where('status', 'failure')
                ->where('created_at', '>=', now()->subDays(7))
                ->count();
        }

        return [
            'campuses' => $inactiveCampuses,
            'departments' => $pendingDepartments,
            'programs' => $pendingPrograms,
            'users' => $inactiveUsers,
            'audit-logs' => $recentFailures,
        ];
    }
}
