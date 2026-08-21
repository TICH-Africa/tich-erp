<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\Staff;
use App\Models\StaffAttendance;
use App\Services\StaffPortalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Carbon\CarbonPeriod;

class AttendanceReviewController extends Controller
{
    public function __construct(
        protected StaffPortalService $staffPortal,
    ) {}

    public function index(Request $request): View
    {
        $period = $request->string('period', 'this_month')->toString();

        [$start, $end] = $this->resolvePeriod($period, $request);

        $attendance = StaffAttendance::query()
            ->with(['staff.department', 'hrReviewedBy'])
            ->when($start, fn ($q) => $q->whereDate('attendance_date', '>=', $start))
            ->when($end, fn ($q) => $q->whereDate('attendance_date', '<=', $end))
            ->when($request->filled('hr_status'), fn ($q) => $q->where('hr_review_status', $request->string('hr_status')))
            ->when($request->filled('staff_id'), fn ($q) => $q->where('staff_id', $request->input('staff_id')))
            ->orderByDesc('attendance_date')
            ->orderByDesc('clock_in_time')
            ->paginate(30)
            ->withQueryString();

        $pendingCount = StaffAttendance::query()
            ->where('hr_review_status', StaffAttendance::HR_STATUS_PENDING)
            ->whereNotNull('clock_in_time')
            ->whereNull('clock_out_time')
            ->count();

        return view('hr.attendance.index', [
            'attendance' => $attendance,
            'period' => $period,
            'start' => $start,
            'end' => $end,
            'pendingCount' => $pendingCount,
        ]);
    }

    public function show(StaffAttendance $attendance): View
    {
        $attendance->load(['staff.department', 'hrReviewedBy']);

        return view('hr.attendance.show', [
            'attendance' => $attendance,
        ]);
    }

    public function approve(Request $request, StaffAttendance $attendance): RedirectResponse
    {
        $validated = $request->validate([
            'hr_notes' => 'nullable|string|max:2000',
        ]);

        $reviewer = $this->staffPortal->staffForUser($request->user());
        abort_unless($reviewer, 403);

        $attendance->update([
            'hr_review_status' => StaffAttendance::HR_STATUS_APPROVED,
            'hr_reviewed_by_staff_id' => $reviewer->id,
            'hr_reviewed_at' => now(),
            'hr_review_notes' => $validated['hr_notes'] ?? null,
        ]);

        return back()->with('success', 'Clock-in approved by HR.');
    }

    public function reject(Request $request, StaffAttendance $attendance): RedirectResponse
    {
        $validated = $request->validate([
            'rejection_reason' => 'required|string|max:1000',
            'hr_notes' => 'nullable|string|max:2000',
        ]);

        $reviewer = $this->staffPortal->staffForUser($request->user());
        abort_unless($reviewer, 403);

        $attendance->update([
            'hr_review_status' => StaffAttendance::HR_STATUS_REJECTED,
            'hr_rejection_reason' => $validated['rejection_reason'],
            'hr_reviewed_by_staff_id' => $reviewer->id,
            'hr_reviewed_at' => now(),
            'hr_review_notes' => $validated['hr_notes'] ?? null,
        ]);

        return back()->with('success', 'Clock-in rejected by HR.');
    }

    private function resolvePeriod(string $period, Request $request): array
    {
        return match ($period) {
            'today' => [now()->toDateString(), now()->toDateString()],
            'this_week' => [now()->startOfWeek()->toDateString(), now()->endOfWeek()->toDateString()],
            'this_month' => [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()],
            'this_year' => [now()->startOfYear()->toDateString(), now()->endOfYear()->toDateString()],
            'last_month' => [now()->subMonth()->startOfMonth()->toDateString(), now()->subMonth()->endOfMonth()->toDateString()],
            'last_quarter' => [now()->subMonths(3)->toDateString(), now()->toDateString()],
            'custom' => [$request->input('start_date'), $request->input('end_date')],
            default => [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()],
        };
    }
}
