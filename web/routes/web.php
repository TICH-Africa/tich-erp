<?php

use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\CampusController;
use App\Http\Controllers\Admin\DepartmentController;
use App\Http\Controllers\Admin\DepartmentGroupController;
use App\Http\Controllers\Admin\ProgramController;
use App\Http\Controllers\Admin\RoleCategoryController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\UserAccessController;
use App\Http\Controllers\AuditController;
use App\Http\Controllers\AuditVerifyController;
use App\Http\Controllers\Auth\WebAuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepartmentDashboardController;
use App\Http\Controllers\Public\ApplicationController;
use App\Http\Controllers\Public\HomeController;
use App\Http\Controllers\Public\ProgramsController;
use App\Http\Controllers\Academics\AttendanceLedgerController;
use App\Http\Controllers\Academics\LessonPlanController;
use App\Http\Controllers\Academics\PerformanceTerminalController;
use App\Http\Controllers\Academics\CalendarController as AcademicsCalendarController;
use App\Http\Controllers\Academics\DashboardController as AcademicsDashboardController;
use App\Http\Controllers\Academics\DepartmentController as AcademicsDepartmentController;
use App\Http\Controllers\Academics\ProgramCurriculumController;
use App\Http\Controllers\Academics\UnitController as AcademicsUnitController;
use App\Http\Controllers\Staff\AttendanceSheetController;
use App\Http\Controllers\Staff\StaffPortalActionController;
use App\Http\Controllers\Staff\StaffPortalDashboardController;
use App\Http\Controllers\Staff\StaffPortalTimetableController;
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
            Route::put('/users/{user}/access', [UserAccessController::class, 'update'])->name('admin.users.update');

            Route::get('/roles', [RoleController::class, 'index'])->name('admin.roles.index');
            Route::post('/roles', [RoleController::class, 'store'])->name('admin.roles.store');
            Route::put('/roles/{role}', [RoleController::class, 'update'])->name('admin.roles.update');
            Route::delete('/roles/{role}', [RoleController::class, 'destroy'])->name('admin.roles.destroy');

            Route::get('/role-categories', [RoleCategoryController::class, 'index'])->name('admin.role-categories.index');
            Route::post('/role-categories/reorder', [RoleCategoryController::class, 'reorder'])->name('admin.role-categories.reorder');
            Route::post('/role-categories', [RoleCategoryController::class, 'store'])->name('admin.role-categories.store');
            Route::put('/role-categories/{roleCategory}', [RoleCategoryController::class, 'update'])->name('admin.role-categories.update');
            Route::delete('/role-categories/{roleCategory}', [RoleCategoryController::class, 'destroy'])->name('admin.role-categories.destroy');
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
        Route::get('/students/{student}/transcript', [\App\Http\Controllers\Sis\TranscriptController::class, 'show'])->name('sis.students.transcript');
        Route::get('/students/{student}/transcript/pdf', [\App\Http\Controllers\Sis\TranscriptController::class, 'pdf'])->name('sis.students.transcript.pdf');
    });

    Route::prefix('hr')->middleware(['permission:hr.staff.view'])->group(function () {
        Route::get('/', [\App\Http\Controllers\HR\DashboardController::class, '__invoke'])->name('hr.dashboard');

        Route::middleware('permission:hr.staff.view')->group(function () {
            Route::get('/staff', [\App\Http\Controllers\HR\StaffViewController::class, 'index'])->name('hr.staff.index');
            Route::get('/staff/create', [\App\Http\Controllers\HR\StaffViewController::class, 'create'])->name('hr.staff.create');
            Route::post('/staff', [\App\Http\Controllers\HR\StaffViewController::class, 'store'])->name('hr.staff.store');
            Route::get('/staff/{staff}', [\App\Http\Controllers\HR\StaffViewController::class, 'show'])->name('hr.staff.show');
            Route::get('/staff/{staff}/edit', [\App\Http\Controllers\HR\StaffViewController::class, 'edit'])->name('hr.staff.edit');
            Route::put('/staff/{staff}', [\App\Http\Controllers\HR\StaffViewController::class, 'update'])->name('hr.staff.update');
            Route::delete('/staff/{staff}', [\App\Http\Controllers\HR\StaffViewController::class, 'destroy'])->name('hr.staff.destroy');
            Route::get('/onboarding', [\App\Http\Controllers\HR\OnboardingViewController::class, 'index'])->name('hr.onboarding.index');
            Route::get('/onboarding/create', [\App\Http\Controllers\HR\OnboardingViewController::class, 'create'])->name('hr.onboarding.create');
            Route::post('/onboarding', [\App\Http\Controllers\HR\OnboardingViewController::class, 'store'])->name('hr.onboarding.store');
            Route::get('/contracts', [\App\Http\Controllers\HR\ContractViewController::class, 'index'])->name('hr.contracts.index');
            Route::get('/contracts/create', [\App\Http\Controllers\HR\ContractViewController::class, 'create'])->name('hr.contracts.create');
            Route::post('/contracts', [\App\Http\Controllers\HR\ContractViewController::class, 'store'])->name('hr.contracts.store');
            Route::get('/contracts/{contract}', [\App\Http\Controllers\HR\ContractViewController::class, 'show'])->name('hr.contracts.show');
            Route::get('/contracts/{contract}/edit', [\App\Http\Controllers\HR\ContractViewController::class, 'edit'])->name('hr.contracts.edit');
            Route::put('/contracts/{contract}', [\App\Http\Controllers\HR\ContractViewController::class, 'update'])->name('hr.contracts.update');
            Route::delete('/contracts/{contract}', [\App\Http\Controllers\HR\ContractViewController::class, 'destroy'])->name('hr.contracts.destroy');
            Route::post('/contracts/{contract}/renew', [\App\Http\Controllers\HR\ContractController::class, 'renew'])->name('hr.contracts.renew');
            Route::post('/contracts/{contract}/convert-permanent', [\App\Http\Controllers\HR\ContractController::class, 'convertToPermanent'])->name('hr.contracts.convert-permanent');
            Route::post('/contracts/{contract}/sign', [\App\Http\Controllers\HR\ContractController::class, 'sign'])->name('hr.contracts.sign');
            Route::get('/vacancies', [\App\Http\Controllers\HR\VacancyViewController::class, 'index'])->name('hr.vacancies.index');
            Route::get('/vacancies/create', [\App\Http\Controllers\HR\VacancyViewController::class, 'create'])->name('hr.vacancies.create');
            Route::post('/vacancies', [\App\Http\Controllers\HR\VacancyViewController::class, 'store'])->name('hr.vacancies.store');
            Route::get('/vacancies/{vacancy}', [\App\Http\Controllers\HR\VacancyViewController::class, 'show'])->name('hr.vacancies.show');
            Route::get('/vacancies/{vacancy}/edit', [\App\Http\Controllers\HR\VacancyViewController::class, 'edit'])->name('hr.vacancies.edit');
            Route::put('/vacancies/{vacancy}', [\App\Http\Controllers\HR\VacancyViewController::class, 'update'])->name('hr.vacancies.update');
            Route::delete('/vacancies/{vacancy}', [\App\Http\Controllers\HR\VacancyViewController::class, 'destroy'])->name('hr.vacancies.destroy');
            Route::post('/vacancies/{vacancy}/toggle-publish', [\App\Http\Controllers\HR\VacancyController::class, 'togglePublish'])->name('hr.vacancies.toggle-publish');
            Route::get('/recruitment', [\App\Http\Controllers\HR\RecruitmentController::class, 'index'])->name('hr.recruitment.index');
            Route::get('/recruitment/{application}', [\App\Http\Controllers\HR\RecruitmentController::class, 'show'])->name('hr.recruitment.show');
            Route::put('/recruitment/{application}', [\App\Http\Controllers\HR\RecruitmentController::class, 'update'])->name('hr.recruitment.update');
            Route::post('/recruitment/{application}/shortlist', [\App\Http\Controllers\HR\RecruitmentController::class, 'shortlist'])->name('hr.recruitment.shortlist');
            Route::post('/recruitment/{application}/reject', [\App\Http\Controllers\HR\RecruitmentController::class, 'reject'])->name('hr.recruitment.reject');
            Route::post('/recruitment/{application}/approve', [\App\Http\Controllers\HR\RecruitmentController::class, 'approve'])->name('hr.recruitment.approve');
            Route::post('/recruitment/{application}/send-qualified-email', [\App\Http\Controllers\HR\RecruitmentController::class, 'sendQualifiedEmail'])->name('hr.recruitment.send-qualified-email');
        });
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
            Route::get('/programs/{program}/timetables/{timetable}/print', [ProgramCurriculumController::class, 'printTimetable'])->name('departments.academics.programs.timetable.print');
            Route::get('/programs/{program}/timetables/{timetable}/pdf', [ProgramCurriculumController::class, 'downloadTimetablePdf'])->name('departments.academics.programs.timetable.pdf');
            Route::get('/attendance-ledger', [AttendanceLedgerController::class, 'index'])->name('departments.academics.attendance-ledger.index');
            Route::get('/lesson-plans', [LessonPlanController::class, 'index'])->name('departments.academics.lesson-plans.index');
            Route::get('/lesson-plans/audit', [LessonPlanController::class, 'audit'])->name('departments.academics.lesson-plans.audit');
            Route::get('/lesson-plans/{plan}', [LessonPlanController::class, 'show'])->name('departments.academics.lesson-plans.show');
            Route::get('/performance', [PerformanceTerminalController::class, 'index'])->name('departments.academics.performance.index');
        });

        Route::middleware('permission:academics.write')->group(function () {
            Route::post('/programs/{program}/allocations', [ProgramCurriculumController::class, 'storeAllocation'])->name('departments.academics.programs.allocations.store');
            Route::delete('/programs/{program}/allocations/{allocation}', [ProgramCurriculumController::class, 'destroyAllocation'])->name('departments.academics.programs.allocations.destroy');
            Route::post('/attendance-ledger/{session}/verify-hod', [AttendanceLedgerController::class, 'verifyHod'])->name('departments.academics.attendance-ledger.verify-hod');
            Route::post('/attendance-ledger/{session}/verify-registrar', [AttendanceLedgerController::class, 'verifyRegistrar'])->name('departments.academics.attendance-ledger.verify-registrar');
            Route::put('/lesson-plans/{plan}', [LessonPlanController::class, 'update'])->name('departments.academics.lesson-plans.update');
            Route::post('/lesson-plans/{plan}/approve', [LessonPlanController::class, 'approve'])->name('departments.academics.lesson-plans.approve');
            Route::post('/lesson-plans/{plan}/reject', [LessonPlanController::class, 'reject'])->name('departments.academics.lesson-plans.reject');
            Route::post('/lesson-plans/{plan}/request-modification', [LessonPlanController::class, 'requestModification'])->name('departments.academics.lesson-plans.request-modification');
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
            Route::put('/programs/{program}/timetable/slots', [ProgramCurriculumController::class, 'syncTimetableKindSlots'])->name('departments.academics.programs.timetable.sync-kind-slots');
            Route::post('/programs/{program}/intakes/{version}/timetable/generate', [ProgramCurriculumController::class, 'generateTimetable'])->name('departments.academics.programs.timetable.generate');
            Route::post('/programs/{program}/timetables/{timetable}/sessions', [ProgramCurriculumController::class, 'addTimetableSession'])->name('departments.academics.programs.timetable.add-session');
            Route::patch('/programs/{program}/timetables/{timetable}/sessions/{session}/move', [ProgramCurriculumController::class, 'moveTimetableSession'])->name('departments.academics.programs.timetable.move-session');
            Route::post('/programs/{program}/timetables/{timetable}/publish', [ProgramCurriculumController::class, 'publishTimetable'])->name('departments.academics.programs.timetable.publish');
            Route::put('/programs/{program}/exam-schedules/{schedule}', [ProgramCurriculumController::class, 'updateExamSchedule'])->name('departments.academics.programs.exam-schedules.update');
            Route::put('/programs/{program}/units/{unit}/assessment-weights', [ProgramCurriculumController::class, 'updateUnitAssessmentWeights'])->name('departments.academics.programs.units.assessment-weights.update');
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
        Route::get('/timetables/{timetable}/print', [\App\Http\Controllers\Portal\PortalTimetableController::class, 'print'])
            ->name('portal.timetable.print');
        Route::get('/timetables/{timetable}/pdf', [\App\Http\Controllers\Portal\PortalTimetableController::class, 'pdf'])
            ->name('portal.timetable.pdf');
        Route::get('/transcript/print', [\App\Http\Controllers\Portal\PortalTranscriptController::class, 'print'])
            ->name('portal.transcript.print');
        Route::get('/transcript/pdf', [\App\Http\Controllers\Portal\PortalTranscriptController::class, 'pdf'])
            ->name('portal.transcript.pdf');
    });

    Route::middleware('staff.portal')->prefix('staff')->group(function () {
        Route::get('/', StaffPortalDashboardController::class)->name('staff.dashboard');
        Route::get('/timetables/{timetable}/print', [StaffPortalTimetableController::class, 'print'])
            ->name('staff.timetable.print');
        Route::get('/timetables/{timetable}/pdf', [StaffPortalTimetableController::class, 'pdf'])
            ->name('staff.timetable.pdf');
        Route::post('/lesson-plans', [StaffPortalActionController::class, 'storeLessonPlan'])->name('staff.lesson-plans.store');
        Route::put('/lesson-plans/{plan}', [StaffPortalActionController::class, 'updateLessonPlan'])->name('staff.lesson-plans.update');
        Route::post('/lesson-plans/{plan}/submit', [StaffPortalActionController::class, 'submitLessonPlan'])->name('staff.lesson-plans.submit');
        Route::post('/attendance', [StaffPortalActionController::class, 'storeAttendanceSession'])->name('staff.attendance.store');
        Route::post('/attendance/sync-timetable', [StaffPortalActionController::class, 'syncAttendanceFromTimetable'])->name('staff.attendance.sync-timetable');
        Route::post('/attendance/{session}/submit-roster', [StaffPortalActionController::class, 'submitForRosterVerification'])->name('staff.attendance.submit-roster');
        Route::post('/attendance/{session}', [StaffPortalActionController::class, 'saveAttendance'])->name('staff.attendance.save');
        Route::post('/attendance/{session}/sheet', [StaffPortalActionController::class, 'uploadAttendanceSheet'])->name('staff.attendance.sheet.upload');
        Route::post('/attendance/{session}/class-photo', [StaffPortalActionController::class, 'uploadClassPhoto'])->name('staff.attendance.class-photo.upload');
        Route::post('/attendance/{session}/verify-roster', [AttendanceLedgerController::class, 'verifyRoster'])->name('departments.academics.attendance-ledger.verify-roster');
        Route::post('/attendance/{session}/exam-eligibility', [AttendanceLedgerController::class, 'examEligibilityCheck'])->name('departments.academics.attendance-ledger.exam-eligibility');
        Route::get('/attendance/{session}/sheet', [AttendanceSheetController::class, 'show'])->name('staff.attendance.sheet');
        Route::post('/grading', [StaffPortalActionController::class, 'storeCatScore'])->name('staff.grading.store');
        Route::post('/grading/grid', [StaffPortalActionController::class, 'saveGradingGrid'])->name('staff.grading.grid');
        Route::post('/grading/exams', [StaffPortalActionController::class, 'saveExamMarks'])->name('staff.grading.exams');
        Route::post('/grading/objective', [StaffPortalActionController::class, 'storeObjectiveAssessment'])->name('staff.grading.objective.store');
        Route::post('/grading/objective/responses', [StaffPortalActionController::class, 'saveObjectiveResponses'])->name('staff.grading.objective.responses');
        Route::post('/grading/objective/grade', [StaffPortalActionController::class, 'runObjectiveAutoGrade'])->name('staff.grading.objective.grade');
        Route::post('/content', [StaffPortalActionController::class, 'storeContent'])->name('staff.content.store');
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

Route::get('/careers', [\App\Http\Controllers\Public\CareerController::class, 'index'])->name('careers.index');
Route::get('/careers/{vacancy}', [\App\Http\Controllers\Public\CareerController::class, 'show'])->name('careers.show');

Route::prefix('vacancies')->group(function () {
    Route::get('/apply/{vacancy}', [\App\Http\Controllers\Public\VacancyApplicationController::class, 'create'])->name('vacancies.apply.create');
    Route::post('/apply/{vacancy}', [\App\Http\Controllers\Public\VacancyApplicationController::class, 'store'])->name('vacancies.apply.store');
    Route::get('/apply/confirmation/{application}', [\App\Http\Controllers\Public\VacancyApplicationController::class, 'confirmation'])->name('vacancies.apply.confirmation');
    Route::match(['get', 'post'], '/track', [\App\Http\Controllers\Public\VacancyApplicationController::class, 'track'])->name('vacancies.track');
});
