<?php

use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\CampusController;
use App\Http\Controllers\Admin\DepartmentController;
use App\Http\Controllers\Admin\DepartmentGroupController;
use App\Http\Controllers\Admin\ProgramController;
use App\Http\Controllers\Admin\UserAccessController;
use App\Http\Controllers\AuditController;
use App\Http\Controllers\AuditVerifyController;
use App\Http\Controllers\Auth\WebAuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepartmentDashboardController;
use App\Http\Controllers\Public\ApplicationController;
use App\Http\Controllers\Public\HomeController;
use App\Http\Controllers\Public\ProgramsController;
use App\Http\Controllers\Academics\CalendarController as AcademicsCalendarController;
use App\Http\Controllers\Academics\DashboardController as AcademicsDashboardController;
use App\Http\Controllers\Academics\DepartmentController as AcademicsDepartmentController;
use App\Http\Controllers\Academics\ProgramCurriculumController;
use App\Http\Controllers\Academics\UnitController as AcademicsUnitController;
use App\Http\Controllers\Admissions\ApprovalController;
use App\Http\Controllers\Admissions\ApplicationDocumentController;
use App\Models\Department;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/programs', [ProgramsController::class, 'index'])->name('programs.index');

/*
|--------------------------------------------------------------------------
| Authentication (Web)
|--------------------------------------------------------------------------
*/

Route::middleware('guest')->group(function () {
    Route::get('/login', [WebAuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [WebAuthController::class, 'login']);
    Route::get('/register', [WebAuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [WebAuthController::class, 'register']);
    Route::get('/forgot-password', [WebAuthController::class, 'showForgotPassword'])->name('password.request');
    Route::post('/forgot-password', [WebAuthController::class, 'sendResetLink'])->name('password.email');
    Route::get('/reset-password/{token}', [WebAuthController::class, 'showResetPassword'])->name('password.reset');
    Route::post('/reset-password', [WebAuthController::class, 'resetPassword'])->name('password.update');

    Route::get('/portal/activate/{token}', [\App\Http\Controllers\Portal\PortalActivationController::class, 'show'])->name('portal.activate');
    Route::post('/portal/activate/{token}', [\App\Http\Controllers\Portal\PortalActivationController::class, 'store'])->name('portal.activate.store');
});

Route::middleware('auth')->group(function () {
    Route::get('/mfa/setup', [WebAuthController::class, 'showMfaSetup'])->name('mfa.setup');
    Route::post('/mfa/setup', [WebAuthController::class, 'setupMfa']);
    Route::get('/mfa/verify', [WebAuthController::class, 'showMfaVerify'])->name('mfa.verify');
    Route::post('/mfa/verify', [WebAuthController::class, 'verifyMfa']);
    Route::post('/mfa/resend', [WebAuthController::class, 'resendMfaCode'])->name('mfa.resend');
    Route::post('/logout', [WebAuthController::class, 'logout'])->name('logout');
});

/*
|--------------------------------------------------------------------------
| Protected application routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'mfa.setup', 'mfa'])->group(function () {
    Route::get('/dashboard', DashboardController::class)
        ->middleware('permission:dashboard.access')
        ->name('dashboard');

    Route::get('/departments/{department}', [DepartmentDashboardController::class, 'show'])
        ->middleware('permission:dashboard.access')
        ->where('department', '[0-9]+(-[0-9]+)?')
        ->name('departments.show');

    Route::prefix('admin')->group(function () {
        Route::get('/', [AdminDashboardController::class, 'index'])
            ->middleware('permission:admin.access')
            ->name('admin.index');

        Route::middleware(['permission:campuses.manage'])->group(function () {
            Route::get('/campuses', [CampusController::class, 'index'])->name('admin.campuses.index');
            Route::post('/campuses', [CampusController::class, 'store'])->name('admin.campuses.store');
            Route::put('/campuses/{campus}', [CampusController::class, 'update'])->name('admin.campuses.update');
        });

        Route::middleware(['permission:departments.manage'])->group(function () {
            Route::get('/department-groups', [DepartmentGroupController::class, 'index'])->name('admin.department-groups.index');
            Route::post('/department-groups', [DepartmentGroupController::class, 'store'])->name('admin.department-groups.store');
            Route::put('/department-groups/{departmentGroup}', [DepartmentGroupController::class, 'update'])->name('admin.department-groups.update');

            Route::get('/departments', [DepartmentController::class, 'index'])->name('admin.departments.index');
            Route::post('/departments', [DepartmentController::class, 'store'])->name('admin.departments.store');
            Route::put('/departments/{department}', [DepartmentController::class, 'update'])->name('admin.departments.update');
        });

        Route::middleware(['permission:programs.manage'])->group(function () {
            Route::get('/programs', [ProgramController::class, 'index'])->name('admin.programs.index');
            Route::post('/programs', [ProgramController::class, 'store'])->name('admin.programs.store');
            Route::put('/programs/{program}', [ProgramController::class, 'update'])->name('admin.programs.update');
        });

        Route::middleware(['permission:users.access.manage'])->group(function () {
            Route::get('/users', [UserAccessController::class, 'index'])->name('admin.users.index');
            Route::get('/users/{user}/access', [UserAccessController::class, 'edit'])->name('admin.users.edit');
            Route::put('/users/{user}/access', [UserAccessController::class, 'update'])->name('admin.users.update');
        });

        Route::prefix('audit-logs')->middleware(['permission:audit_logs.read'])->group(function () {
            Route::get('/', [AuditController::class, 'index'])->name('admin.audit-logs.index');
            Route::get('/verify', AuditVerifyController::class)->name('admin.audit-logs.verify');
            Route::get('/{id}', [AuditController::class, 'show'])->name('admin.audit-logs.show');
        });
    });

    Route::prefix('admissions')->middleware(['permission:admissions.read'])->group(function () {
        Route::get('/', [ApprovalController::class, 'dashboard'])->name('admissions.dashboard');
        Route::get('/applications', [ApprovalController::class, 'index'])->name('admissions.applications.index');
        Route::get('/applications/{id}', [ApprovalController::class, 'show'])->name('admissions.applications.show');
        Route::get('/applications/{applicationId}/documents/{documentId}', [ApplicationDocumentController::class, 'show'])
            ->name('admissions.applications.documents.show');
        Route::get('/applications/{applicationId}/documents/{documentId}/download', [ApplicationDocumentController::class, 'download'])
            ->name('admissions.applications.documents.download');
        Route::post('/applications/{id}/shortlist', [ApprovalController::class, 'shortlist'])
            ->middleware('permission:admissions.write')
            ->name('admissions.applications.shortlist');
        Route::post('/applications/{id}/approve', [ApprovalController::class, 'approve'])
            ->middleware('permission:admissions.approve')
            ->name('admissions.applications.approve');
        Route::post('/applications/{id}/reject', [ApprovalController::class, 'reject'])
            ->middleware('permission:admissions.approve')
            ->name('admissions.applications.reject');
        Route::post('/applications/{id}/resend-portal-signup', [ApprovalController::class, 'resendPortalSignup'])
            ->middleware('permission:admissions.write')
            ->name('admissions.applications.resend-portal-signup');
    });

    Route::prefix('sis')->middleware(['permission:students.read'])->group(function () {
        Route::get('/students', [\App\Http\Controllers\Sis\StudentController::class, 'index'])->name('sis.students.index');
        Route::get('/students/{student}', [\App\Http\Controllers\Sis\StudentController::class, 'show'])->name('sis.students.show');
    });

    Route::get('/academics', function () {
        $hub = Department::findAcademicsHub();
        abort_unless($hub, 404);

        return redirect()->route('departments.academics.dashboard', $hub);
    })->middleware('permission:academics.read')->name('academics.hub');

    Route::prefix('departments/{department}/academics')->group(function () {
        Route::get('/', AcademicsDashboardController::class)
            ->middleware('permission:academics.read')
            ->name('departments.academics.dashboard');

        Route::middleware('permission:academics.read')->group(function () {
            Route::get('/departments', [AcademicsDepartmentController::class, 'index'])->name('departments.academics.departments.index');
            Route::get('/units', [AcademicsUnitController::class, 'index'])->name('departments.academics.units.index');
            Route::get('/programs', [ProgramCurriculumController::class, 'index'])->name('departments.academics.programs.index');
            Route::get('/programs/{program}/curriculum', [ProgramCurriculumController::class, 'show'])->name('departments.academics.programs.curriculum');
        });

        Route::middleware('permission:academics.write')->group(function () {
            Route::put('/learning-departments/{learningDepartment}/profile', [AcademicsDepartmentController::class, 'updateProfile'])
                ->name('departments.academics.departments.update-profile');
            Route::post('/units', [AcademicsUnitController::class, 'store'])->name('departments.academics.units.store');
            Route::put('/units/{unit}', [AcademicsUnitController::class, 'update'])->name('departments.academics.units.update');
            Route::post('/units/{unit}/submit', [AcademicsUnitController::class, 'submit'])->name('departments.academics.units.submit');
            Route::put('/programs/{program}/format', [ProgramCurriculumController::class, 'updateFormat'])->name('departments.academics.programs.update-format');
            Route::post('/programs/{program}/units', [ProgramCurriculumController::class, 'syncUnits'])->name('departments.academics.programs.sync-units');
            Route::post('/programs/{program}/intakes', [ProgramCurriculumController::class, 'createVersion'])->name('departments.academics.programs.intakes.store');
            Route::post('/programs/{program}/intakes/{version}/units', [ProgramCurriculumController::class, 'syncIntakeUnits'])->name('departments.academics.programs.intakes.sync-units');
            Route::post('/programs/{program}/intakes/{version}/periods', [ProgramCurriculumController::class, 'syncIntakePeriods'])->name('departments.academics.programs.intakes.sync-periods');
            Route::put('/programs/{program}/timetable/template', [ProgramCurriculumController::class, 'syncTimetableTemplate'])->name('departments.academics.programs.timetable.sync-template');
            Route::post('/programs/{program}/intakes/{version}/timetable/generate', [ProgramCurriculumController::class, 'generateTimetable'])->name('departments.academics.programs.timetable.generate');
            Route::post('/programs/{program}/timetables/{timetable}/sessions', [ProgramCurriculumController::class, 'addTimetableSession'])->name('departments.academics.programs.timetable.add-session');
            Route::post('/programs/{program}/timetables/{timetable}/publish', [ProgramCurriculumController::class, 'publishTimetable'])->name('departments.academics.programs.timetable.publish');
            Route::post('/programs/{program}/intakes/{version}/semesters/{semester}/units', [ProgramCurriculumController::class, 'addIntakeUnit'])->name('departments.academics.programs.intakes.add-unit');
            Route::post('/programs/{program}/versions', [ProgramCurriculumController::class, 'createVersion'])->name('departments.academics.programs.versions.create');
            Route::post('/versions/{version}/submit', [ProgramCurriculumController::class, 'submitVersion'])->name('departments.academics.versions.submit');
            Route::post('/versions/{version}/reopen', [ProgramCurriculumController::class, 'reopenVersion'])->name('departments.academics.versions.reopen');
        });

        Route::post('/units/{unit}/approve', [AcademicsUnitController::class, 'approve'])
            ->middleware('permission:academics.approve')
            ->name('departments.academics.units.approve');

        Route::post('/versions/{version}/approve-registry', [ProgramCurriculumController::class, 'approveVersionRegistry'])
            ->middleware('permission:academics.approve')
            ->name('departments.academics.versions.approve-registry');

        Route::post('/versions/{version}/approve-ceo', [ProgramCurriculumController::class, 'approveVersionCeo'])
            ->middleware('permission:academics.approve')
            ->name('departments.academics.versions.approve-ceo');

        Route::middleware('permission:academics.calendar')->group(function () {
            Route::get('/calendar', [AcademicsCalendarController::class, 'index'])->name('departments.academics.calendar.index');
            Route::post('/calendar/years', [AcademicsCalendarController::class, 'storeYear'])->name('departments.academics.calendar.store-year');
            Route::put('/calendar/semesters/{semester}', [AcademicsCalendarController::class, 'updateSemester'])->name('departments.academics.calendar.update-semester');
        });
    });

    Route::get('/portal', [\App\Http\Controllers\Portal\PortalDashboardController::class, '__invoke'])
        ->middleware('student.portal')
        ->name('portal.dashboard');
    Route::middleware('student.portal')->prefix('portal')->group(function () {
        Route::get('/documents/{document}', [\App\Http\Controllers\Portal\PortalDocumentController::class, 'show'])
            ->name('portal.documents.show');
        Route::get('/documents/{document}/download', [\App\Http\Controllers\Portal\PortalDocumentController::class, 'download'])
            ->name('portal.documents.download');
    });
});

/*
|--------------------------------------------------------------------------
| Public Application Portal
|--------------------------------------------------------------------------
*/

Route::prefix('apply')->group(function () {
    Route::get('/', [ApplicationController::class, 'index'])->name('apply.index');
    Route::post('/step/{step}', [ApplicationController::class, 'handleStep'])->name('apply.step');
    Route::get('/confirmation/{number}', [ApplicationController::class, 'confirmation'])->name('apply.confirmation');
    Route::match(['get', 'post'], '/check-status', [ApplicationController::class, 'checkStatus'])->name('apply.status');
    Route::post('/reset', [ApplicationController::class, 'reset'])->name('apply.reset');
});
