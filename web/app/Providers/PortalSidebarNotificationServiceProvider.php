<?php

namespace App\Providers;

use App\Models\AttendanceSession;
use App\Models\CurriculumVersion;
use App\Models\Feedback;
use App\Models\Grievance;
use App\Models\LessonPlan;
use App\Models\LeaveRequest;
use App\Models\PolicyAcknowledgement;
use App\Models\Staff;
use App\Models\StaffDocument;
use App\Models\StaffProfileChangeRequest;
use App\Models\Student;
use App\Models\Unit;
use App\Services\Sidebar\PortalSidebarBroadcastService;
use Illuminate\Support\ServiceProvider;

class PortalSidebarNotificationServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $broadcastStaff = function (?int $staffId): void {
            if (! $staffId) {
                return;
            }

            $staff = Staff::query()->find($staffId);
            if ($staff) {
                app(PortalSidebarBroadcastService::class)->broadcastStaffPortals($staff);
            }
        };

        $broadcastStudent = function (?int $studentId): void {
            if (! $studentId) {
                return;
            }

            $student = Student::query()->find($studentId);
            if ($student) {
                app(PortalSidebarBroadcastService::class)->broadcastStudent($student);
            }
        };

        $broadcastAcademicsForUnitDepartment = function (?int $departmentId): void {
            app(PortalSidebarBroadcastService::class)->broadcastAcademicsForDepartmentId($departmentId);
        };

        foreach ([
            LeaveRequest::class => fn (LeaveRequest $model) => $broadcastStaff($model->staff_id),
            StaffProfileChangeRequest::class => fn (StaffProfileChangeRequest $model) => $broadcastStaff($model->staff_id),
            Grievance::class => fn (Grievance $model) => $broadcastStaff($model->staff_id),
            Feedback::class => fn (Feedback $model) => $broadcastStaff($model->staff_id),
            PolicyAcknowledgement::class => fn (PolicyAcknowledgement $model) => $broadcastStaff($model->staff_id),
            StaffDocument::class => fn (StaffDocument $model) => $broadcastStaff($model->staff_id),
        ] as $modelClass => $callback) {
            $modelClass::saved($callback);
            $modelClass::deleted($callback);
        }

        foreach ([Student::class] as $modelClass) {
            $modelClass::saved(fn (Student $student) => $broadcastStudent($student->id));
            $modelClass::deleted(fn (Student $student) => $broadcastStudent($student->id));
        }

        LessonPlan::saved(function (LessonPlan $plan) use ($broadcastStaff, $broadcastAcademicsForUnitDepartment): void {
            $broadcastStaff($plan->prepared_by);
            $plan->loadMissing('allocation.unit');
            $broadcastAcademicsForUnitDepartment($plan->allocation?->unit?->department_id);
        });

        LessonPlan::deleted(function (LessonPlan $plan) use ($broadcastStaff, $broadcastAcademicsForUnitDepartment): void {
            $broadcastStaff($plan->prepared_by);
            $plan->loadMissing('allocation.unit');
            $broadcastAcademicsForUnitDepartment($plan->allocation?->unit?->department_id);
        });

        AttendanceSession::saved(function (AttendanceSession $session) use ($broadcastStaff, $broadcastAcademicsForUnitDepartment): void {
            $broadcastStaff($session->recorded_by);
            $session->loadMissing('allocation.unit');
            $broadcastAcademicsForUnitDepartment($session->allocation?->unit?->department_id);
        });

        AttendanceSession::deleted(function (AttendanceSession $session) use ($broadcastStaff, $broadcastAcademicsForUnitDepartment): void {
            $broadcastStaff($session->recorded_by);
            $session->loadMissing('allocation.unit');
            $broadcastAcademicsForUnitDepartment($session->allocation?->unit?->department_id);
        });

        Unit::saved(fn (Unit $unit) => $broadcastAcademicsForUnitDepartment($unit->department_id));
        Unit::deleted(fn (Unit $unit) => $broadcastAcademicsForUnitDepartment($unit->department_id));

        CurriculumVersion::saved(function (CurriculumVersion $version) use ($broadcastAcademicsForUnitDepartment): void {
            $version->loadMissing('program');
            $broadcastAcademicsForUnitDepartment($version->program?->department_id);
        });

        CurriculumVersion::deleted(function (CurriculumVersion $version) use ($broadcastAcademicsForUnitDepartment): void {
            $version->loadMissing('program');
            $broadcastAcademicsForUnitDepartment($version->program?->department_id);
        });
    }
}
