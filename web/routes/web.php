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
use App\Http\Controllers\Admissions\ApprovalController;
use App\Http\Controllers\Admissions\ApplicationDocumentController;
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

    Route::prefix('academics')->group(function () {
        Route::get('/', [\App\Http\Controllers\Academics\DashboardController::class, '__invoke'])
            ->middleware('permission:academics.read')
            ->name('academics.dashboard');

        Route::middleware('permission:academics.read')->group(function () {
            Route::get('/departments', [\App\Http\Controllers\Academics\DepartmentController::class, 'index'])->name('academics.departments.index');
            Route::get('/units', [\App\Http\Controllers\Academics\UnitController::class, 'index'])->name('academics.units.index');
            Route::get('/programs', [\App\Http\Controllers\Academics\ProgramCurriculumController::class, 'index'])->name('academics.programs.index');
            Route::get('/programs/{program}/curriculum', [\App\Http\Controllers\Academics\ProgramCurriculumController::class, 'show'])->name('academics.programs.curriculum');
        });

        Route::middleware('permission:academics.write')->group(function () {
            Route::post('/departments', [\App\Http\Controllers\Academics\DepartmentController::class, 'store'])->name('academics.departments.store');
            Route::put('/departments/{department}/profile', [\App\Http\Controllers\Academics\DepartmentController::class, 'updateProfile'])->name('academics.departments.update-profile');
            Route::post('/units', [\App\Http\Controllers\Academics\UnitController::class, 'store'])->name('academics.units.store');
            Route::put('/units/{unit}', [\App\Http\Controllers\Academics\UnitController::class, 'update'])->name('academics.units.update');
            Route::post('/units/{unit}/submit', [\App\Http\Controllers\Academics\UnitController::class, 'submit'])->name('academics.units.submit');
            Route::put('/programs/{program}/format', [\App\Http\Controllers\Academics\ProgramCurriculumController::class, 'updateFormat'])->name('academics.programs.update-format');
            Route::post('/programs/{program}/units', [\App\Http\Controllers\Academics\ProgramCurriculumController::class, 'syncUnits'])->name('academics.programs.sync-units');
            Route::post('/programs/{program}/versions', [\App\Http\Controllers\Academics\ProgramCurriculumController::class, 'createVersion'])->name('academics.programs.versions.create');
            Route::post('/versions/{version}/submit', [\App\Http\Controllers\Academics\ProgramCurriculumController::class, 'submitVersion'])->name('academics.versions.submit');
        });

        Route::post('/departments/{department}/approve-ceo', [\App\Http\Controllers\Academics\DepartmentController::class, 'approveCeo'])
            ->middleware('permission:academics.approve')
            ->name('academics.departments.approve-ceo');

        Route::post('/units/{unit}/approve', [\App\Http\Controllers\Academics\UnitController::class, 'approve'])
            ->middleware('permission:academics.approve')
            ->name('academics.units.approve');

        Route::post('/versions/{version}/approve-registry', [\App\Http\Controllers\Academics\ProgramCurriculumController::class, 'approveVersionRegistry'])
            ->middleware('permission:academics.approve')
            ->name('academics.versions.approve-registry');

        Route::post('/versions/{version}/approve-ceo', [\App\Http\Controllers\Academics\ProgramCurriculumController::class, 'approveVersionCeo'])
            ->middleware('permission:academics.approve')
            ->name('academics.versions.approve-ceo');

        Route::middleware('permission:academics.calendar')->group(function () {
            Route::get('/calendar', [\App\Http\Controllers\Academics\CalendarController::class, 'index'])->name('academics.calendar.index');
            Route::post('/calendar/years', [\App\Http\Controllers\Academics\CalendarController::class, 'storeYear'])->name('academics.calendar.store-year');
            Route::put('/calendar/semesters/{semester}', [\App\Http\Controllers\Academics\CalendarController::class, 'updateSemester'])->name('academics.calendar.update-semester');
        });
    });

    Route::get('/portal', [\App\Http\Controllers\Portal\PortalDashboardController::class, '__invoke'])
        ->middleware('student.portal')
        ->name('portal.dashboard');
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
