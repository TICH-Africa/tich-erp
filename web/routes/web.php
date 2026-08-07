<?php

use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\CampusController;
use App\Http\Controllers\Admin\DepartmentController;
use App\Http\Controllers\Admin\DepartmentGroupController;
use App\Http\Controllers\Admin\ProgramController;
use App\Http\Controllers\Admin\SiteSettingsController;
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
use App\Http\Controllers\Staff\AttendanceSheetController;
use App\Http\Controllers\Staff\StaffLessonPlanDocumentController;
use App\Http\Controllers\Staff\StaffPortalActionController;
use App\Http\Controllers\Staff\StaffPortalDashboardController;
use App\Http\Controllers\Staff\StaffPortalTimetableController;
use App\Http\Controllers\Admissions\ApprovalController;
use App\Http\Controllers\Admissions\ApplicationDocumentController;
use App\Http\Controllers\HR\RecruitmentApplicationDocumentController;
use App\Http\Controllers\HR\EssOnboardingController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/about', [HomeController::class, 'about'])->name('about');
Route::get('/research', [HomeController::class, 'research'])->name('research');
Route::get('/support', [HomeController::class, 'support'])->name('support');
Route::get('/contact', [HomeController::class, 'contact'])->name('contact');
Route::get('/events', [HomeController::class, 'events'])->name('events');
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

    Route::get('/onboarding/activate/{token}', [EssOnboardingController::class, 'show'])->name('ess.onboarding.activate');
    Route::post('/onboarding/activate/{token}/draft', [EssOnboardingController::class, 'saveDraft'])->name('ess.onboarding.draft');
    Route::post('/onboarding/activate/{token}', [EssOnboardingController::class, 'store'])->name('ess.onboarding.activate.store');
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

    Route::prefix('site-settings')->middleware(['permission:site_settings.read'])->group(function () {
        Route::get('/', [SiteSettingsController::class, 'index'])->name('site-settings.index');

        Route::middleware(['permission:site_settings.manage'])->group(function () {
            Route::put('/general', [SiteSettingsController::class, 'updateGeneral'])->name('site-settings.general.update');
            Route::post('/hero-slides', [SiteSettingsController::class, 'storeSlide'])->name('site-settings.hero-slides.store');
            Route::put('/hero-slides/{slide}', [SiteSettingsController::class, 'updateSlide'])->name('site-settings.hero-slides.update');
            Route::delete('/hero-slides/{slide}', [SiteSettingsController::class, 'destroySlide'])->name('site-settings.hero-slides.destroy');
            Route::post('/hero-slides/reorder', [SiteSettingsController::class, 'reorderSlides'])->name('site-settings.hero-slides.reorder');
            Route::post('/contacts', [SiteSettingsController::class, 'storeContact'])->name('site-settings.contacts.store');
            Route::put('/contacts/{contact}', [SiteSettingsController::class, 'updateContact'])->name('site-settings.contacts.update');
            Route::delete('/contacts/{contact}', [SiteSettingsController::class, 'destroyContact'])->name('site-settings.contacts.destroy');
            Route::post('/contacts/reorder', [SiteSettingsController::class, 'reorderContacts'])->name('site-settings.contacts.reorder');
            Route::post('/social-links', [SiteSettingsController::class, 'storeSocialLink'])->name('site-settings.social-links.store');
            Route::put('/social-links/{socialLink}', [SiteSettingsController::class, 'updateSocialLink'])->name('site-settings.social-links.update');
            Route::delete('/social-links/{socialLink}', [SiteSettingsController::class, 'destroySocialLink'])->name('site-settings.social-links.destroy');
            Route::post('/social-links/reorder', [SiteSettingsController::class, 'reorderSocialLinks'])->name('site-settings.social-links.reorder');
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

    Route::redirect('/sis', '/sis/students')
        ->middleware('permission:students.read')
        ->name('sis.dashboard');

    Route::prefix('sis')->middleware(['permission:students.read'])->group(function () {
        Route::get('/students', [\App\Http\Controllers\Sis\StudentController::class, 'index'])->name('sis.students.index');
        Route::get('/students/{student}', [\App\Http\Controllers\Sis\StudentController::class, 'show'])->name('sis.students.show');
        Route::get('/students/{student}/transcript', [\App\Http\Controllers\Sis\TranscriptController::class, 'show'])->name('sis.students.transcript');
        Route::get('/students/{student}/transcript/pdf', [\App\Http\Controllers\Sis\TranscriptController::class, 'pdf'])->name('sis.students.transcript.pdf');
    });

    Route::prefix('hr')->middleware(['permission:hr.staff.view'])->group(function () {
        Route::get('/', [\App\Http\Controllers\HR\DashboardController::class, '__invoke'])->name('hr.dashboard');
        Route::get('/sidebar-notifications', \App\Http\Controllers\HR\SidebarNotificationController::class)->name('hr.sidebar-notifications');

        Route::middleware('permission:hr.staff.view')->group(function () {
            Route::get('/staff', [\App\Http\Controllers\HR\StaffViewController::class, 'index'])->name('hr.staff.index');
            Route::get('/staff/create', [\App\Http\Controllers\HR\StaffViewController::class, 'create'])->name('hr.staff.create');
            Route::post('/staff', [\App\Http\Controllers\HR\StaffViewController::class, 'store'])->name('hr.staff.store');
            Route::get('/staff/{staff}', [\App\Http\Controllers\HR\StaffViewController::class, 'show'])->name('hr.staff.show');
            Route::get('/profile-changes', [\App\Http\Controllers\HR\StaffProfileChangeController::class, 'index'])->name('hr.profile-changes.index');
            Route::get('/profile-changes/{profileChange}', [\App\Http\Controllers\HR\StaffProfileChangeController::class, 'show'])->name('hr.profile-changes.show');
            Route::post('/profile-changes/{profileChange}/approve', [\App\Http\Controllers\HR\StaffProfileChangeController::class, 'approve'])->name('hr.profile-changes.approve');
            Route::post('/profile-changes/{profileChange}/reject', [\App\Http\Controllers\HR\StaffProfileChangeController::class, 'reject'])->name('hr.profile-changes.reject');
            Route::get('/staff/{staff}/edit', [\App\Http\Controllers\HR\StaffViewController::class, 'edit'])->name('hr.staff.edit');
            Route::put('/staff/{staff}', [\App\Http\Controllers\HR\StaffViewController::class, 'update'])->name('hr.staff.update');
            Route::delete('/staff/{staff}', [\App\Http\Controllers\HR\StaffViewController::class, 'destroy'])->name('hr.staff.destroy');
            Route::get('/onboarding', [\App\Http\Controllers\HR\OnboardingViewController::class, 'index'])->name('hr.onboarding.index');
            Route::get('/onboarding/create', [\App\Http\Controllers\HR\OnboardingViewController::class, 'create'])->name('hr.onboarding.create');
            Route::post('/onboarding', [\App\Http\Controllers\HR\OnboardingViewController::class, 'store'])->name('hr.onboarding.store');
            Route::get('/onboarding/{onboarding}/review', [\App\Http\Controllers\HR\EssOnboardingController::class, 'review'])->name('hr.onboarding.review');
            Route::post('/onboarding/{onboarding}/approve', [\App\Http\Controllers\HR\EssOnboardingController::class, 'approve'])->name('hr.onboarding.approve');
            Route::post('/onboarding/{onboarding}/reject', [\App\Http\Controllers\HR\EssOnboardingController::class, 'reject'])->name('hr.onboarding.reject');
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
            Route::get('/recruitment/{application}/documents/{documentKey}', [\App\Http\Controllers\HR\RecruitmentApplicationDocumentController::class, 'show'])
                ->name('hr.recruitment.documents.show');
            Route::get('/recruitment/{application}/documents/{documentKey}/download', [\App\Http\Controllers\HR\RecruitmentApplicationDocumentController::class, 'download'])
                ->name('hr.recruitment.documents.download');
            Route::get('/recruitment/{application}/documents/{documentKey}/viewer', [\App\Http\Controllers\HR\RecruitmentApplicationDocumentController::class, 'viewer'])
                ->name('hr.recruitment.documents.viewer');
            Route::put('/recruitment/{application}', [\App\Http\Controllers\HR\RecruitmentController::class, 'update'])->name('hr.recruitment.update');
            Route::post('/recruitment/{application}/shortlist', [\App\Http\Controllers\HR\RecruitmentController::class, 'shortlist'])->name('hr.recruitment.shortlist');
            Route::post('/recruitment/{application}/reject', [\App\Http\Controllers\HR\RecruitmentController::class, 'reject'])->name('hr.recruitment.reject');
            Route::post('/recruitment/{application}/approve', [\App\Http\Controllers\HR\RecruitmentController::class, 'approve'])->name('hr.recruitment.approve');
            Route::post('/recruitment/{application}/send-qualified-email', [\App\Http\Controllers\HR\RecruitmentController::class, 'sendQualifiedEmail'])->name('hr.recruitment.send-qualified-email');
            Route::get('/leave/overview', [\App\Http\Controllers\HR\LeaveRequestController::class, 'overview'])->name('hr.leave.overview');
            Route::get('/leave/employees', [\App\Http\Controllers\HR\LeaveRequestController::class, 'employees'])->name('hr.leave.employees');
            Route::get('/leave', [\App\Http\Controllers\HR\LeaveRequestController::class, 'index'])->name('hr.leave.index');
            Route::get('/leave/{leaveRequest}', [\App\Http\Controllers\HR\LeaveRequestController::class, 'show'])->name('hr.leave.show');
            Route::post('/leave/{leaveRequest}/approve', [\App\Http\Controllers\HR\LeaveRequestController::class, 'approve'])
                ->middleware('permission:hr.manage_leave')
                ->name('hr.leave.approve');
            Route::post('/leave/{leaveRequest}/reject', [\App\Http\Controllers\HR\LeaveRequestController::class, 'reject'])
                ->middleware('permission:hr.manage_leave')
                ->name('hr.leave.reject');
            Route::post('/leave/{leaveRequest}/return', [\App\Http\Controllers\HR\LeaveRequestController::class, 'returnForChanges'])
                ->middleware('permission:hr.manage_leave')
                ->name('hr.leave.return');
            Route::get('/payroll', [\App\Http\Controllers\HR\PayrollController::class, 'index'])->name('hr.payroll.index');
            Route::get('/payroll/report', [\App\Http\Controllers\HR\PayrollController::class, 'report'])->name('hr.payroll.report');
            Route::get('/payroll/report/pdf', [\App\Http\Controllers\HR\PayrollController::class, 'reportPdf'])->name('hr.payroll.report.pdf');
            Route::redirect('payroll/tax', 'payroll');
            Route::redirect('payroll/tax/settings', 'payroll/settings');
            Route::middleware('permission:hr.manage_contracts')->group(function () {
                Route::get('/payroll/settings', [\App\Http\Controllers\HR\PayrollController::class, 'settings'])->name('hr.payroll.settings');
                Route::put('/payroll/settings', [\App\Http\Controllers\HR\PayrollController::class, 'updateSettings'])->name('hr.payroll.settings.update');
            });
        Route::get('/policies', [\App\Http\Controllers\HR\HrPolicyController::class, 'index'])->name('hr.policies.index');
        Route::get('/policies/create', [\App\Http\Controllers\HR\HrPolicyController::class, 'create'])->name('hr.policies.create');
        Route::post('/policies', [\App\Http\Controllers\HR\HrPolicyController::class, 'store'])->name('hr.policies.store');
        Route::get('/policies/{policy}', [\App\Http\Controllers\HR\HrPolicyController::class, 'show'])->name('hr.policies.show');
        Route::get('/policies/{policy}/edit', [\App\Http\Controllers\HR\HrPolicyController::class, 'edit'])->name('hr.policies.edit');
        Route::put('/policies/{policy}', [\App\Http\Controllers\HR\HrPolicyController::class, 'update'])->name('hr.policies.update');
        Route::delete('/policies/{policy}', [\App\Http\Controllers\HR\HrPolicyController::class, 'destroy'])->name('hr.policies.destroy');
        Route::get('/policies/{policy}/download', [\App\Http\Controllers\HR\HrPolicyController::class, 'download'])->name('hr.policies.download');
        Route::get('/policies/{policy}/view', [\App\Http\Controllers\HR\HrPolicyController::class, 'view'])->name('hr.policies.view');
        Route::get('/policies/{policy}/send', [\App\Http\Controllers\HR\HrPolicyController::class, 'sendForm'])->name('hr.policies.send');
        Route::post('/policies/{policy}/send', [\App\Http\Controllers\HR\HrPolicyController::class, 'sendToStaff'])->name('hr.policies.send.store');
        Route::get('/policies/{policy}/acknowledgements', [\App\Http\Controllers\HR\HrPolicyController::class, 'acknowledgements'])->name('hr.policies.acknowledgements');
        Route::get('/policies/acknowledgements', [\App\Http\Controllers\HR\HrPolicyController::class, 'acknowledgementsIndex'])->name('hr.policies.acknowledgements.index');

        Route::get('/policies/assigned', [\App\Http\Controllers\HR\HrPolicyController::class, 'assigned'])->name('hr.policies.assigned');
            Route::get('/staff/{staff}/documents/create', [\App\Http\Controllers\HR\StaffDocumentController::class, 'create'])->name('hr.staff.documents.create');
            Route::post('/staff/{staff}/documents', [\App\Http\Controllers\HR\StaffDocumentController::class, 'store'])->name('hr.staff.documents.store');
            Route::get('/staff/{staff}/documents/send', [\App\Http\Controllers\HR\StaffDocumentController::class, 'sendForm'])->name('hr.staff.documents.send');
            Route::post('/staff/{staff}/documents/send', [\App\Http\Controllers\HR\StaffDocumentController::class, 'sendToStaff'])->name('hr.staff.documents.send.store');
            Route::delete('/staff/{staff}/documents/{document}', [\App\Http\Controllers\HR\StaffDocumentController::class, 'destroy'])->name('hr.staff.documents.destroy');
            Route::get('/staff/{staff}/documents/{document}/download', [\App\Http\Controllers\HR\StaffDocumentController::class, 'download'])->name('hr.staff.documents.download');
            Route::get('/staff/{staff}/documents/{document}/read', [\App\Http\Controllers\HR\StaffDocumentController::class, 'read'])->name('hr.staff.documents.read');
            Route::post('/staff/{staff}/documents/{document}/approve', [\App\Http\Controllers\HR\StaffDocumentController::class, 'approve'])->name('hr.staff.documents.approve');
            Route::post('/staff/{staff}/documents/{document}/reject', [\App\Http\Controllers\HR\StaffDocumentController::class, 'reject'])->name('hr.staff.documents.reject');
            Route::get('/documents', [\App\Http\Controllers\HR\StaffDocumentController::class, 'index'])->name('hr.documents.index');
            Route::get('/documents/staff/{staff}', [\App\Http\Controllers\HR\StaffDocumentController::class, 'show'])->name('hr.documents.show');
            Route::get('/documents/templates', [\App\Http\Controllers\HR\DocumentGenerationController::class, 'index'])->name('hr.documents.templates.index');
            Route::get('/documents/templates/create', [\App\Http\Controllers\HR\DocumentGenerationController::class, 'create'])->name('hr.documents.templates.create');
            Route::post('/documents/templates', [\App\Http\Controllers\HR\DocumentGenerationController::class, 'store'])->name('hr.documents.templates.store');
            Route::get('/documents/templates/{template}/edit', [\App\Http\Controllers\HR\DocumentGenerationController::class, 'edit'])->name('hr.documents.templates.edit');
            Route::put('/documents/templates/{template}', [\App\Http\Controllers\HR\DocumentGenerationController::class, 'update'])->name('hr.documents.templates.update');
            Route::delete('/documents/templates/{template}', [\App\Http\Controllers\HR\DocumentGenerationController::class, 'destroy'])->name('hr.documents.templates.destroy');
            Route::get('/documents/templates/{template}/preview', [\App\Http\Controllers\HR\DocumentGenerationController::class, 'preview'])->name('hr.documents.templates.preview');
            Route::get('/documents/templates/{template}/generate', [\App\Http\Controllers\HR\DocumentGenerationController::class, 'generate'])->name('hr.documents.templates.generate');
            Route::get('/documents/templates/{template}/download', [\App\Http\Controllers\HR\DocumentGenerationController::class, 'download'])->name('hr.documents.templates.download');
            Route::get('/documents/quick/{type}', [\App\Http\Controllers\HR\DocumentGenerationController::class, 'quickDownload'])->name('hr.documents.quick.download');
            Route::get('/training', [\App\Http\Controllers\HR\TrainingController::class, 'index'])->name('hr.training.index');
            Route::get('/training/create', [\App\Http\Controllers\HR\TrainingController::class, 'create'])->name('hr.training.create');
            Route::post('/training', [\App\Http\Controllers\HR\TrainingController::class, 'store'])->name('hr.training.store');
            Route::get('/training/{training}/edit', [\App\Http\Controllers\HR\TrainingController::class, 'edit'])->name('hr.training.edit');
            Route::put('/training/{training}', [\App\Http\Controllers\HR\TrainingController::class, 'update'])->name('hr.training.update');
            Route::delete('/training/{training}', [\App\Http\Controllers\HR\TrainingController::class, 'destroy'])->name('hr.training.destroy');
            Route::get('/offboarding', [\App\Http\Controllers\HR\OffboardingController::class, 'index'])->name('hr.offboarding.index');
            Route::get('/offboarding/create', [\App\Http\Controllers\HR\OffboardingController::class, 'create'])->name('hr.offboarding.create');
            Route::post('/offboarding', [\App\Http\Controllers\HR\OffboardingController::class, 'store'])->name('hr.offboarding.store');
            Route::get('/offboarding/{offboarding}', [\App\Http\Controllers\HR\OffboardingController::class, 'show'])->name('hr.offboarding.show');
            Route::post('/offboarding/{offboarding}/approve', [\App\Http\Controllers\HR\OffboardingController::class, 'approve'])->name('hr.offboarding.approve');
            Route::post('/offboarding/{offboarding}/reject', [\App\Http\Controllers\HR\OffboardingController::class, 'reject'])->name('hr.offboarding.reject');
            Route::post('/offboarding/{offboarding}/start-clearance', [\App\Http\Controllers\HR\OffboardingController::class, 'startClearance'])->name('hr.offboarding.start-clearance');
            Route::post('/offboarding/{offboarding}/complete-clearance', [\App\Http\Controllers\HR\OffboardingController::class, 'completeClearance'])->name('hr.offboarding.complete-clearance');
            Route::post('/offboarding/{offboarding}/items/{item}/complete', [\App\Http\Controllers\HR\OffboardingController::class, 'completeClearanceItem'])->name('hr.offboarding.complete-item');

            Route::prefix('employee-relations')->name('hr.employee-relations.')->group(function () {
                Route::get('/disciplinary', [\App\Http\Controllers\HR\DisciplinaryController::class, 'index'])->name('disciplinary.index');
                Route::get('/disciplinary/create', [\App\Http\Controllers\HR\DisciplinaryController::class, 'create'])->name('disciplinary.create');
                Route::post('/disciplinary', [\App\Http\Controllers\HR\DisciplinaryController::class, 'store'])->name('disciplinary.store');
                Route::get('/disciplinary/{disciplinaryCase}', [\App\Http\Controllers\HR\DisciplinaryController::class, 'show'])->name('disciplinary.show');
                Route::get('/disciplinary/{disciplinaryCase}/edit', [\App\Http\Controllers\HR\DisciplinaryController::class, 'edit'])->name('disciplinary.edit');
                Route::put('/disciplinary/{disciplinaryCase}', [\App\Http\Controllers\HR\DisciplinaryController::class, 'update'])->name('disciplinary.update');
                Route::delete('/disciplinary/{disciplinaryCase}', [\App\Http\Controllers\HR\DisciplinaryController::class, 'destroy'])->name('disciplinary.destroy');

                Route::get('/grievances', [\App\Http\Controllers\HR\GrievanceController::class, 'index'])->name('grievances.index');
                Route::get('/grievances/create', [\App\Http\Controllers\HR\GrievanceController::class, 'create'])->name('grievances.create');
                Route::post('/grievances', [\App\Http\Controllers\HR\GrievanceController::class, 'store'])->name('grievances.store');
                Route::get('/grievances/{grievance}', [\App\Http\Controllers\HR\GrievanceController::class, 'show'])->name('grievances.show');
                Route::get('/grievances/{grievance}/edit', [\App\Http\Controllers\HR\GrievanceController::class, 'edit'])->name('grievances.edit');
                Route::put('/grievances/{grievance}', [\App\Http\Controllers\HR\GrievanceController::class, 'update'])->name('grievances.update');
                Route::delete('/grievances/{grievance}', [\App\Http\Controllers\HR\GrievanceController::class, 'destroy'])->name('grievances.destroy');

                Route::get('/feedback', [\App\Http\Controllers\HR\FeedbackController::class, 'index'])->name('feedback.index');
                Route::get('/feedback/create', [\App\Http\Controllers\HR\FeedbackController::class, 'create'])->name('feedback.create');
                Route::post('/feedback', [\App\Http\Controllers\HR\FeedbackController::class, 'store'])->name('feedback.store');
                Route::get('/feedback/{feedback}', [\App\Http\Controllers\HR\FeedbackController::class, 'show'])->name('feedback.show');
                Route::get('/feedback/{feedback}/edit', [\App\Http\Controllers\HR\FeedbackController::class, 'edit'])->name('feedback.edit');
                Route::put('/feedback/{feedback}', [\App\Http\Controllers\HR\FeedbackController::class, 'update'])->name('feedback.update');
                Route::delete('/feedback/{feedback}', [\App\Http\Controllers\HR\FeedbackController::class, 'destroy'])->name('feedback.destroy');
            });
        });
    });

    $registerAcademicsRoutes = require __DIR__.'/includes/academics.php';

    Route::prefix('academics')
        ->middleware('resolve.academics.hub')
        ->group(function () use ($registerAcademicsRoutes) {
            $registerAcademicsRoutes(true);
        });

    Route::prefix('departments/{department}/academics')
        ->where(['department' => '[0-9]+(-[0-9]+)?'])
        ->middleware('redirect.legacy.academics')
        ->group(function () use ($registerAcademicsRoutes) {
            $registerAcademicsRoutes(false);
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

    Route::get('/lesson-plans/{plan}/print', [StaffLessonPlanDocumentController::class, 'print'])->name('lesson-plans.print');
    Route::get('/lesson-plans/{plan}/pdf', [StaffLessonPlanDocumentController::class, 'pdf'])->name('lesson-plans.pdf');
    Route::get('/lesson-plans/{plan}/upload', [StaffLessonPlanDocumentController::class, 'showUpload'])->name('lesson-plans.upload.show');
    Route::get('/lesson-plans/{plan}/upload/download', [StaffLessonPlanDocumentController::class, 'downloadUpload'])->name('lesson-plans.upload.download');

    Route::get('/employee', [\App\Http\Controllers\Employee\EmployeePortalController::class, '__invoke'])
        ->middleware('employee.portal')
        ->name('employee.dashboard');

    Route::middleware('employee.portal')->prefix('employee')->group(function () {
        Route::get('/profile/edit', [\App\Http\Controllers\Employee\EmployeeProfileController::class, 'edit'])->name('employee.profile.edit');
        Route::post('/profile', [\App\Http\Controllers\Employee\EmployeeProfileController::class, 'update'])->name('employee.profile.update');

        Route::get('/leave', [\App\Http\Controllers\Employee\EmployeeLeaveController::class, 'index'])->name('employee.leave.index');
        Route::post('/leave', [\App\Http\Controllers\Employee\EmployeeLeaveController::class, 'store'])->name('employee.leave.store');
        Route::put('/leave/{leaveRequest}', [\App\Http\Controllers\Employee\EmployeeLeaveController::class, 'update'])->name('employee.leave.update');
        Route::post('/leave/{leaveRequest}/cancel', [\App\Http\Controllers\Employee\EmployeeLeaveController::class, 'cancel'])->name('employee.leave.cancel');

        Route::get('/attendance', [\App\Http\Controllers\Employee\EmployeeAttendanceController::class, 'index'])->name('employee.attendance.index');
        Route::post('/attendance/clock-in', [\App\Http\Controllers\Employee\EmployeeAttendanceController::class, 'clockIn'])->name('employee.attendance.clock-in');
        Route::post('/attendance/clock-out', [\App\Http\Controllers\Employee\EmployeeAttendanceController::class, 'clockOut'])->name('employee.attendance.clock-out');

        Route::get('/policies/assigned', [\App\Http\Controllers\HR\HrPolicyController::class, 'assigned'])->name('policies.assigned');
        Route::get('/policies/{policy}/acknowledge', [\App\Http\Controllers\HR\HrPolicyController::class, 'acknowledgeForm'])->name('policies.acknowledge');
        Route::post('/policies/{policy}/acknowledge', [\App\Http\Controllers\HR\HrPolicyController::class, 'acknowledge'])->name('policies.acknowledge.store');
        Route::get('/policies/{policy}/view', [\App\Http\Controllers\HR\HrPolicyController::class, 'view'])->name('policies.view');
        Route::get('/policies/{policy}/download', [\App\Http\Controllers\HR\HrPolicyController::class, 'download'])->name('policies.download');

        Route::get('/concerns', [\App\Http\Controllers\Employee\EmployeeConcernController::class, 'index'])->name('employee.concerns.index');
        Route::get('/concerns/create', [\App\Http\Controllers\Employee\EmployeeConcernController::class, 'create'])->name('employee.concerns.create');
        Route::post('/concerns', [\App\Http\Controllers\Employee\EmployeeConcernController::class, 'store'])->name('employee.concerns.store');
        Route::get('/concerns/{grievance}', [\App\Http\Controllers\Employee\EmployeeConcernController::class, 'show'])->name('employee.concerns.show');

        Route::prefix('relations')->name('employee.relations.')->group(function () {
            Route::redirect('/grievances', '/employee/concerns');
            Route::redirect('/grievances/create', '/employee/concerns/create');
            Route::get('/grievances/{grievance}', fn (\App\Models\Grievance $grievance) => redirect()->route('employee.concerns.show', $grievance))->name('grievances.show');

            Route::get('/feedback', [\App\Http\Controllers\Employee\EmployeeRelationController::class, 'feedback'])->name('feedback.index');
            Route::get('/feedback/create', [\App\Http\Controllers\Employee\EmployeeRelationController::class, 'feedbackCreate'])->name('feedback.create');
            Route::post('/feedback', [\App\Http\Controllers\Employee\EmployeeRelationController::class, 'feedbackStore'])->name('feedback.store');
            Route::get('/feedback/{feedback}', [\App\Http\Controllers\Employee\EmployeeRelationController::class, 'feedbackShow'])->name('feedback.show');
        });
    });

    Route::middleware('staff.portal')->prefix('staff')->group(function () {
        Route::get('/', StaffPortalDashboardController::class)->name('staff.dashboard');
        Route::get('/timetables/{timetable}/print', [StaffPortalTimetableController::class, 'print'])
            ->name('staff.timetable.print');
        Route::get('/timetables/{timetable}/pdf', [StaffPortalTimetableController::class, 'pdf'])
            ->name('staff.timetable.pdf');
        Route::post('/lesson-plans', [StaffPortalActionController::class, 'storeLessonPlan'])->name('staff.lesson-plans.store');
        Route::get('/lesson-plans/context', [StaffPortalActionController::class, 'lessonPlanContext'])->name('staff.lesson-plans.context');
        Route::put('/lesson-plans/{plan}', [StaffPortalActionController::class, 'updateLessonPlan'])->name('staff.lesson-plans.update');
        Route::post('/lesson-plans/{plan}/submit', [StaffPortalActionController::class, 'submitLessonPlan'])->name('staff.lesson-plans.submit');
        Route::post('/lesson-plans/{plan}/verify', [StaffPortalActionController::class, 'verifyLessonPlan'])->name('staff.lesson-plans.verify');
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
Route::post('/documents', [\App\Http\Controllers\HR\StaffDocumentController::class, 'staffStore'])->name('staff.documents.store');
            Route::get('/documents/create', [\App\Http\Controllers\HR\StaffDocumentController::class, 'staffCreate'])->name('staff.documents.create');
            Route::get('/documents/{document}/download', [\App\Http\Controllers\HR\StaffDocumentController::class, 'staffDownload'])->name('staff.documents.download');
        Route::get('/policies/{policy}/download', [\App\Http\Controllers\HR\HrPolicyController::class, 'download'])->name('staff.policies.download');
        Route::get('/policies/{policy}/view', [\App\Http\Controllers\HR\HrPolicyController::class, 'view'])->name('staff.policies.view');
        Route::get('/policies/{policy}/acknowledge', [\App\Http\Controllers\HR\HrPolicyController::class, 'acknowledgeForm'])->name('staff.policies.acknowledge');
        Route::post('/policies/{policy}/acknowledge', [\App\Http\Controllers\HR\HrPolicyController::class, 'acknowledge'])->name('staff.policies.acknowledge.store');
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
