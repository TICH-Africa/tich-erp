<?php

namespace App\Services;

use App\Models\Grievance;
use App\Models\InAppNotification;
use App\Models\LeaveRequest;
use App\Models\Staff;
use App\Models\StaffDocument;
use App\Models\StaffProfileChangeRequest;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use Throwable;

class NotificationActionUrlResolver
{
    public function resolve(InAppNotification $notification, ?User $viewer = null): ?string
    {
        if (filled($notification->action_url)) {
            return $notification->action_url;
        }

        $type = $notification->related_entity_type;
        $id = $notification->related_entity_id;

        if (! $type || $id === null || $id === '') {
            return null;
        }

        $viewer ??= auth()->user();

        try {
            return match ($type) {
                'leave_request' => $this->leaveUrl($viewer, (string) $id),
                'lesson_plan' => $this->lessonPlanUrl($viewer, (string) $id),
                'employee_concern' => $this->concernUrl($viewer, (string) $id),
                'staff_profile_change' => $this->profileChangeUrl($viewer, (string) $id),
                'staff_document' => $this->documentUrl($viewer, (string) $id),
                'staff_contract' => $this->safeRoute('hr.contracts.show', [(int) $id]),
                'staff_professional_license' => $this->safeRoute('employee.documents.index'),
                'staff' => $this->staffUrl($viewer, (string) $id),
                'user_roles' => $this->safeRoute('dashboard'),
                'attendance_summary' => $this->safeRoute('departments.academics.attendance-ledger.index')
                    ?? $this->safeRoute('staff.dashboard', ['section' => 'attendance']),
                default => null,
            };
        } catch (Throwable) {
            return null;
        }
    }

    private function leaveUrl(?User $viewer, string $id): ?string
    {
        $leave = LeaveRequest::query()->with('staff')->find((int) $id);
        if (! $leave) {
            return $this->safeRoute('employee.leave.index');
        }

        if ($viewer && (int) $leave->staff?->user_id === (int) $viewer->id) {
            return $this->safeRoute('employee.leave.index');
        }

        return $this->safeRoute('hr.leave.show', [$leave])
            ?? $this->safeRoute('employee.leave.index');
    }

    private function lessonPlanUrl(?User $viewer, string $id): ?string
    {
        $academics = $this->safeRoute('departments.academics.lesson-plans.show', [(int) $id]);
        if ($viewer && $viewer->isTeachingStaff()) {
            return $this->safeRoute('staff.dashboard', [
                'section' => 'lesson-plans',
                'edit_plan' => (int) $id,
            ]) ?? $academics;
        }

        return $academics
            ?? $this->safeRoute('staff.dashboard', [
                'section' => 'lesson-plans',
                'edit_plan' => (int) $id,
            ]);
    }

    private function concernUrl(?User $viewer, string $id): ?string
    {
        $grievance = Grievance::query()->with('staff')->find((int) $id);
        if (! $grievance) {
            return $this->safeRoute('employee.concerns.index');
        }

        if ($viewer && (int) $grievance->staff?->user_id === (int) $viewer->id) {
            return $this->safeRoute('employee.concerns.show', [$grievance]);
        }

        return $this->safeRoute('hr.employee-relations.grievances.show', [$grievance])
            ?? $this->safeRoute('employee.concerns.show', [$grievance]);
    }

    private function profileChangeUrl(?User $viewer, string $id): ?string
    {
        $change = StaffProfileChangeRequest::query()->with('staff')->find((int) $id);
        if ($change) {
            if ($viewer && (int) $change->staff?->user_id === (int) $viewer->id) {
                return $this->safeRoute('employee.dashboard');
            }

            return $this->safeRoute('hr.profile-changes.show', [$change])
                ?? $this->safeRoute('hr.profile-changes.index');
        }

        $staff = Staff::query()->find((int) $id);
        if ($staff) {
            if ($viewer && (int) $staff->user_id === (int) $viewer->id) {
                return $this->safeRoute('employee.dashboard');
            }

            return $this->safeRoute('hr.profile-changes.index')
                ?? $this->safeRoute('hr.staff.show', [$staff]);
        }

        return $this->safeRoute('employee.dashboard');
    }

    private function documentUrl(?User $viewer, string $id): ?string
    {
        $document = StaffDocument::query()->with('staff')->find((int) $id);
        if (! $document) {
            return $this->safeRoute('employee.documents.index');
        }

        if ($viewer && (int) $document->staff?->user_id === (int) $viewer->id) {
            return $this->safeRoute('employee.documents.index');
        }

        return $this->safeRoute('hr.documents.show', [$document->staff_id])
            ?? $this->safeRoute('hr.staff.show', [$document->staff_id])
            ?? $this->safeRoute('employee.documents.index');
    }

    private function staffUrl(?User $viewer, string $id): ?string
    {
        $staff = Staff::query()->find((int) $id);
        if (! $staff) {
            return $this->safeRoute('dashboard');
        }

        if ($viewer && (int) $staff->user_id === (int) $viewer->id) {
            return $this->safeRoute('employee.dashboard');
        }

        return $this->safeRoute('hr.staff.show', [$staff])
            ?? $this->safeRoute('employee.dashboard');
    }

    /**
     * @param  array<string|int, mixed>  $parameters
     */
    private function safeRoute(string $name, array $parameters = []): ?string
    {
        if (! Route::has($name)) {
            return null;
        }

        try {
            return route($name, $parameters);
        } catch (Throwable) {
            return null;
        }
    }
}
