<?php

use App\Models\Department;
use App\Models\Student;
use App\Services\AcademicsAccessService;
use App\Services\DepartmentDashboardService;
use App\Services\EmployeePortalService;
use App\Services\RBACService;
use App\Services\StaffPortalService;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('hr.sidebar', function ($user) {
    if (! $user) {
        return false;
    }

    return app(RBACService::class)->hasPermission($user, 'hr.staff.view');
});

Broadcast::channel('admin.sidebar', function ($user) {
    if (! $user) {
        return false;
    }

    return app(RBACService::class)->hasPermission($user, 'admin.access');
});

Broadcast::channel('administration.sidebar', function ($user) {
    if (! $user) {
        return false;
    }

    return app(RBACService::class)->hasPermission($user, 'administration.read');
});

Broadcast::channel('finance.sidebar', function ($user) {
    if (! $user) {
        return false;
    }

    return app(RBACService::class)->hasPermission($user, 'finance.read');
});

Broadcast::channel('employee.sidebar.{userId}', function ($user, $userId) {
    if (! $user || (int) $user->id !== (int) $userId) {
        return false;
    }

    return app(EmployeePortalService::class)->staffForUser($user) !== null;
});

Broadcast::channel('staff.sidebar.{userId}', function ($user, $userId) {
    if (! $user || (int) $user->id !== (int) $userId) {
        return false;
    }

    $staff = app(StaffPortalService::class)->staffForUser($user);
    if (! $staff) {
        return false;
    }

    $rbac = app(RBACService::class);
    $teachingRoles = ['Lecturer/Tutor', 'HOD', 'Dean of Students', 'Academic Registrar', 'Super Admin'];
    $hasTeachingRole = collect($teachingRoles)->contains(fn (string $role) => $rbac->hasRole($user, $role));

    return $staff->is_teaching_staff
        || $hasTeachingRole
        || $rbac->hasPermission($user, 'academics.read');
});

Broadcast::channel('student.sidebar.{userId}', function ($user, $userId) {
    if (! $user || (int) $user->id !== (int) $userId) {
        return false;
    }

    return $user->student_id || Student::query()->where('user_id', $user->id)->exists();
});

Broadcast::channel('academics.sidebar.{departmentId}', function ($user, $departmentId) {
    if (! $user) {
        return false;
    }

    $rbac = app(RBACService::class);
    if (! $rbac->hasPermission($user, 'academics.read')) {
        return false;
    }

    $department = Department::query()->find($departmentId);
    if (! $department?->isAcademicsHub()) {
        return false;
    }

    return app(AcademicsAccessService::class)->canAccessAll($user)
        || app(DepartmentDashboardService::class)->userCanAccessDepartment($user, $department);
});
