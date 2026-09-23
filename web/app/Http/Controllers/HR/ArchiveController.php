<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\Staff;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ArchiveController extends Controller
{
    public function __construct(
        protected AuditService $auditService,
    ) {}

    public function index(Request $request): View
    {
        $query = Staff::withTrashed()
            ->whereNotNull('archived_at')
            ->with(['archivedBy:id,first_name,surname,employee_number', 'department:id,dept_name'])
            ->orderByDesc('archived_at');

        if ($search = $request->string('search')->trim()) {
            $query->where(function ($q) use ($search) {
                $q->where('employee_number', 'like', "%{$search}%")
                    ->orWhere('first_name', 'like', "%{$search}%")
                    ->orWhere('middle_name', 'like', "%{$search}%")
                    ->orWhere('surname', 'like', "%{$search}%")
                    ->orWhere('primary_email', 'like', "%{$search}%")
                    ->orWhere('organisation_email', 'like', "%{$search}%");
            });
        }

        if ($reason = $request->string('reason')->trim()) {
            $query->where('archive_reason', 'like', "%{$reason}%");
        }

        $archivedStaff = $query->paginate(25)->appends($request->query());

        return view('hr.archive.index', [
            'archivedStaff' => $archivedStaff,
            'filters' => $request->only(['search', 'reason']),
        ]);
    }

    public function archive(Request $request, Staff $staff): RedirectResponse
    {
        $validated = $request->validate([
            'archive_reason' => ['required', 'string', 'max:1000'],
        ]);

        $staff->update([
            'archived_at' => now(),
            'archived_by' => $request->user()?->staff_id,
            'archive_reason' => $validated['archive_reason'],
        ]);

        $staff->delete();

        $this->auditService->log(
            'hr.staff.archived',
            'staff',
            $staff->id,
            ['employment_status' => $staff->employment_status],
            ['archived_at' => now(), 'archive_reason' => $validated['archive_reason']],
            null,
            'success',
            $request->user()->id,
            $request
        );

        return redirect()->route('hr.staff.index')->with('status', 'Staff archived successfully.');
    }

    public function restore(Request $request, string $staff): RedirectResponse
    {
        $staffModel = Staff::withTrashed()->findOrFail($staff);
        $staffModel->restore();
        $staffModel->update([
            'archived_at' => null,
            'archived_by' => null,
            'archive_reason' => null,
        ]);

        if ($staffModel->user) {
            $staffModel->user->update(['is_active' => true]);
        }

        $this->auditService->log(
            'hr.staff.restored',
            'staff',
            $staffModel->id,
            ['archived_at' => $staffModel->archived_at, 'archive_reason' => $staffModel->archive_reason],
            ['archived_at' => null],
            null,
            'success',
            $request->user()->id,
            $request
        );

        return redirect()->route('hr.archive.index')->with('status', 'Staff restored successfully.');
    }

    public function destroy(Request $request, string $staff): RedirectResponse
    {
        $staffModel = Staff::withTrashed()->findOrFail($staff);
        $staffModel->forceDelete();

        $this->auditService->log(
            'hr.staff.permanently_deleted',
            'staff',
            $staffModel->id,
            ['archived_at' => $staffModel->archived_at, 'archive_reason' => $staffModel->archive_reason],
            null,
            null,
            'success',
            $request->user()->id,
            $request
        );

        return redirect()->route('hr.archive.index')->with('status', 'Staff permanently deleted.');
    }

    public function deactivate(Request $request, string $staff): RedirectResponse
    {
        $staffModel = Staff::withTrashed()->findOrFail($staff);

        if ($staffModel->user) {
            $staffModel->user->update(['is_active' => false]);
        }

        $this->auditService->log(
            'hr.staff.user.deactivated',
            'staff',
            $staffModel->id,
            ['user_active' => $staffModel->user?->is_active],
            ['user_active' => false],
            null,
            'success',
            $request->user()->id,
            $request
        );

        return redirect()->route('hr.archive.index')->with('status', 'Staff user account deactivated.');
    }
}