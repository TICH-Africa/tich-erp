<?php

namespace App\Services;

use App\Models\LeaveRequest;
use App\Models\RecruitmentApplication;
use App\Models\Staff;
use Illuminate\Support\Facades\DB;

class HrDashboardStatsService
{
    public function chartData(): array
    {
        return [
            'staffByStatus' => $this->staffByStatus(),
            'staffByDepartment' => $this->staffByDepartment(),
            'leaveByStatus' => $this->leaveByStatus(),
            'applicationsByStatus' => $this->applicationsByStatus(),
        ];
    }

    private function staffByStatus(): array
    {
        $rows = Staff::query()
            ->select('employment_status', DB::raw('COUNT(*) as total'))
            ->groupBy('employment_status')
            ->orderByDesc('total')
            ->get();

        return [
            'labels' => $rows->map(fn ($row) => $this->label($row->employment_status))->all(),
            'values' => $rows->pluck('total')->map(fn ($v) => (int) $v)->all(),
        ];
    }

    private function staffByDepartment(): array
    {
        $rows = Staff::query()
            ->leftJoin('departments', 'staff.department_id', '=', 'departments.id')
            ->select(DB::raw("COALESCE(departments.dept_name, 'Unassigned') as label"), DB::raw('COUNT(*) as total'))
            ->groupBy('label')
            ->orderByDesc('total')
            ->limit(8)
            ->get();

        return [
            'labels' => $rows->pluck('label')->all(),
            'values' => $rows->pluck('total')->map(fn ($v) => (int) $v)->all(),
        ];
    }

    private function leaveByStatus(): array
    {
        $rows = LeaveRequest::query()
            ->where('is_cancelled', false)
            ->select('overall_status', DB::raw('COUNT(*) as total'))
            ->groupBy('overall_status')
            ->orderByDesc('total')
            ->get();

        return [
            'labels' => $rows->map(fn ($row) => $this->label($row->overall_status))->all(),
            'values' => $rows->pluck('total')->map(fn ($v) => (int) $v)->all(),
        ];
    }

    private function applicationsByStatus(): array
    {
        $rows = RecruitmentApplication::query()
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->orderByDesc('total')
            ->get();

        return [
            'labels' => $rows->map(fn ($row) => $this->label($row->status))->all(),
            'values' => $rows->pluck('total')->map(fn ($v) => (int) $v)->all(),
        ];
    }

    private function label(?string $value): string
    {
        if ($value === null || $value === '') {
            return 'Unknown';
        }

        return ucfirst(str_replace('_', ' ', $value));
    }
}
