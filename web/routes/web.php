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
use App\Http\Controllers\Auth\ErpRegistrationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepartmentDashboardController;
use App\Http\Controllers\Module\BudgetingController as ModuleBudgetingController;
use App\Http\Controllers\Public\ApplicationController;
use App\Http\Controllers\Public\ApplicationPaymentController;
use App\Http\Controllers\Public\FaviconController;
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

Route::get('/favicon.ico', FaviconController::class)->name('favicon');
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
    Route::get('/register', fn () => redirect()
        ->route('login')
        ->with('status', 'ERP registration is by invitation only. Contact ICT or HR if you need staff access.'));
    Route::get('/register/invite/{token}', [ErpRegistrationController::class, 'showInvite'])->name('register.invite');
    Route::post('/register/invite/{token}', [ErpRegistrationController::class, 'storeInvite'])->name('register.invite.store');
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

Route::middleware(['auth', 'mfa.setup', 'mfa', 'employee.profile.complete'])->group(function () {
    Route::get('/start', \App\Http\Controllers\AccountStartController::class)->name('account.start');

    Route::get('/dashboard', DashboardController::class)
        ->middleware('permission:dashboard.access')
        ->name('dashboard');

    Route::get('/departments/{department}', [DepartmentDashboardController::class, 'show'])
        ->middleware('permission:dashboard.access')
        ->where('department', '[0-9]+(-[0-9]+)?')
        ->name('departments.show');

    $registerModuleBudgeting = static function (string $module): void {
        if ($module === 'finance') {
            Route::get('/budgeting', [ModuleBudgetingController::class, 'index'])->name('finance.budget-requests.index');
            Route::get('/budgeting/create', [ModuleBudgetingController::class, 'create'])->name('finance.budget-requests.create');
            Route::post('/budgeting', [ModuleBudgetingController::class, 'store'])->name('finance.budget-requests.store');
            Route::get('/budgeting/{budgetRequest}/edit', [ModuleBudgetingController::class, 'edit'])->name('finance.budget-requests.edit');
            Route::put('/budgeting/{budgetRequest}', [ModuleBudgetingController::class, 'update'])->name('finance.budget-requests.update');

            return;
        }

        Route::get('/budgeting', [ModuleBudgetingController::class, 'index'])->name("{$module}.budgeting.index");
        Route::get('/budgeting/create', [ModuleBudgetingController::class, 'create'])->name("{$module}.budgeting.create");
        Route::post('/budgeting', [ModuleBudgetingController::class, 'store'])->name("{$module}.budgeting.store");
        Route::get('/budgeting/{budgetRequest}/edit', [ModuleBudgetingController::class, 'edit'])->name("{$module}.budgeting.edit");
        Route::put('/budgeting/{budgetRequest}', [ModuleBudgetingController::class, 'update'])->name("{$module}.budgeting.update");
    };

    Route::prefix('admin')->group(function () {
        Route::get('/', [AdminDashboardController::class, 'index'])
            ->middleware('permission:admin.access')
            ->name('admin.index');

        Route::get('/sidebar-notifications', \App\Http\Controllers\Admin\SidebarNotificationController::class)
            ->middleware('permission:admin.access')
            ->name('admin.sidebar-notifications');

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
            Route::put('/roles/{role}/permissions', [RoleController::class, 'updatePermissions'])->name('admin.roles.permissions.update');
            Route::delete('/roles/{role}', [RoleController::class, 'destroy'])->name('admin.roles.destroy');

            Route::get('/role-categories', [RoleCategoryController::class, 'index'])->name('admin.role-categories.index');
            Route::post('/role-categories/reorder', [RoleCategoryController::class, 'reorder'])->name('admin.role-categories.reorder');
            Route::post('/role-categories', [RoleCategoryController::class, 'store'])->name('admin.role-categories.store');
            Route::put('/role-categories/{roleCategory}', [RoleCategoryController::class, 'update'])->name('admin.role-categories.update');
            Route::delete('/role-categories/{roleCategory}', [RoleCategoryController::class, 'destroy'])->name('admin.role-categories.destroy');
        });

        Route::prefix('audit-logs')->middleware(['permission:audit_logs.read'])->group(function () {
            Route::get('/', [AuditController::class, 'index'])->name('admin.audit-logs.index');
            Route::get('/export', [AuditController::class, 'export'])->name('admin.audit-logs.export');
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
        Route::post('/applications/{id}/handoff-academics', [ApprovalController::class, 'handoffToAcademics'])
            ->middleware('permission:admissions.write')
            ->name('admissions.applications.handoff-academics');
        Route::post('/applications/{id}/confirm-payment', [ApprovalController::class, 'confirmPayment'])
            ->middleware('permission:admissions.write')
            ->name('admissions.applications.confirm-payment');
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

    Route::prefix('finance')->middleware(['permission:finance.read'])->group(function () use ($registerModuleBudgeting) {
        Route::get('/', [\App\Http\Controllers\Finance\DashboardController::class, '__invoke'])->name('finance.dashboard');
        Route::get('/sidebar-notifications', \App\Http\Controllers\Finance\SidebarNotificationController::class)->name('finance.sidebar-notifications');
        $registerModuleBudgeting('finance');
        Route::get('/student-finance', [\App\Http\Controllers\Finance\FinanceHubController::class, 'studentFinance'])->name('finance.student-finance.hub');
        Route::get('/records', [\App\Http\Controllers\Finance\FinanceHubController::class, 'records'])->name('finance.records.index');
        Route::get('/employee', [\App\Http\Controllers\Finance\FinanceHubController::class, 'employee'])->name('finance.employee.index');

        Route::prefix('employee/payroll')->name('finance.employee.payroll.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Finance\PayrollController::class, 'index'])->name('index');
            Route::get('/report', [\App\Http\Controllers\Finance\PayrollController::class, 'report'])->name('report');
            Route::get('/report/pdf', [\App\Http\Controllers\Finance\PayrollController::class, 'reportPdf'])->name('report.pdf');
            Route::middleware('permission:finance.read')->group(function () {
                Route::get('/runs', [\App\Http\Controllers\Finance\PayrollRunController::class, 'index'])->name('runs.index');
                Route::get('/runs/create', [\App\Http\Controllers\Finance\PayrollRunController::class, 'create'])->name('runs.create');
                Route::post('/runs', [\App\Http\Controllers\Finance\PayrollRunController::class, 'store'])->name('runs.store');
                Route::get('/runs/{payrollRun}', [\App\Http\Controllers\Finance\PayrollRunController::class, 'show'])->name('runs.show');
                Route::post('/runs/{payrollRun}/recalculate', [\App\Http\Controllers\Finance\PayrollRunController::class, 'recalculate'])->name('runs.recalculate');
                Route::post('/runs/{payrollRun}/approve', [\App\Http\Controllers\Finance\PayrollRunController::class, 'approve'])->name('runs.approve');
                Route::post('/runs/{payrollRun}/cancel', [\App\Http\Controllers\Finance\PayrollRunController::class, 'cancel'])->name('runs.cancel');
                Route::get('/runs/{payrollRun}/statutory/{agency}', [\App\Http\Controllers\Finance\PayrollRunController::class, 'exportStatutory'])->name('runs.statutory.export');
                Route::get('/items/{payrollItem}/payslip', [\App\Http\Controllers\Finance\PayrollRunController::class, 'itemPayslip'])->name('runs.item.payslip');
                Route::get('/items/{payrollItem}/payslip/pdf', [\App\Http\Controllers\Finance\PayrollRunController::class, 'itemPayslipPdf'])->name('runs.item.payslip.pdf');
                Route::get('/settings', [\App\Http\Controllers\Finance\PayrollController::class, 'settings'])->name('settings');
                Route::put('/settings', [\App\Http\Controllers\Finance\PayrollController::class, 'updateSettings'])->name('settings.update');
            });
        });

        Route::get('/ar', [\App\Http\Controllers\Finance\AccountsReceivableController::class, 'indexGlobal'])->name('finance.ar.hub');

        Route::get('/fee-structures', [\App\Http\Controllers\Finance\FeeStructureController::class, 'index'])->name('finance.fee-structures.index');
        Route::middleware('permission:finance.fee_structures.manage')->group(function () {
            Route::get('/fee-structures/create', [\App\Http\Controllers\Finance\FeeStructureController::class, 'create'])->name('finance.fee-structures.create');
            Route::post('/fee-structures', [\App\Http\Controllers\Finance\FeeStructureController::class, 'store'])->name('finance.fee-structures.store');
            Route::get('/fee-structures/{feeStructure}/edit', [\App\Http\Controllers\Finance\FeeStructureController::class, 'edit'])->name('finance.fee-structures.edit');
            Route::put('/fee-structures/{feeStructure}', [\App\Http\Controllers\Finance\FeeStructureController::class, 'update'])->name('finance.fee-structures.update');
            Route::post('/fee-structures/{feeStructure}/approve', [\App\Http\Controllers\Finance\FeeStructureController::class, 'approve'])->name('finance.fee-structures.approve');
        });
        Route::get('/fee-structures/{feeStructure}', [\App\Http\Controllers\Finance\FeeStructureController::class, 'show'])->name('finance.fee-structures.show');

        Route::get('/student-accounts', [\App\Http\Controllers\Finance\StudentAccountController::class, 'index'])->name('finance.student-accounts.index');
        Route::get('/student-accounts/{studentAccount}', [\App\Http\Controllers\Finance\StudentAccountController::class, 'show'])->name('finance.student-accounts.show');

        Route::get('/invoices', [\App\Http\Controllers\Finance\InvoiceController::class, 'index'])->name('finance.invoices.index');
        Route::get('/invoices/{invoice}', [\App\Http\Controllers\Finance\InvoiceController::class, 'show'])->name('finance.invoices.show');
        Route::middleware('permission:finance.invoices.manage')->group(function () {
            Route::get('/invoices/create/new', [\App\Http\Controllers\Finance\InvoiceController::class, 'create'])->name('finance.invoices.create');
            Route::post('/invoices', [\App\Http\Controllers\Finance\InvoiceController::class, 'store'])->name('finance.invoices.store');
            Route::post('/invoices/{invoice}/resend', [\App\Http\Controllers\Finance\InvoiceController::class, 'resend'])->name('finance.invoices.resend');
        });

        Route::get('/payments', [\App\Http\Controllers\Finance\PaymentController::class, 'index'])->name('finance.payments.index');
        Route::middleware('permission:finance.payments.manage')->group(function () {
            Route::get('/payments/create/new', [\App\Http\Controllers\Finance\PaymentController::class, 'create'])->name('finance.payments.create');
            Route::post('/payments', [\App\Http\Controllers\Finance\PaymentController::class, 'store'])->name('finance.payments.store');
            Route::get('/mpesa/settings', [\App\Http\Controllers\Finance\MpesaSettingsController::class, 'edit'])->name('finance.mpesa.settings');
            Route::put('/mpesa/settings', [\App\Http\Controllers\Finance\MpesaSettingsController::class, 'update'])->name('finance.mpesa.settings.update');
            Route::post('/mpesa/stk/{stkRequest}/reconcile', [\App\Http\Controllers\Finance\MpesaSettingsController::class, 'reconcile'])->name('finance.mpesa.stk.reconcile');
        });

        Route::get('/ledger', [\App\Http\Controllers\Finance\LedgerController::class, 'index'])->name('finance.ledger.index');
        Route::get('/reports', [\App\Http\Controllers\Finance\LedgerController::class, 'reports'])->name('finance.reports.index');
        Route::get('/reports/view/pdf', [\App\Http\Controllers\Finance\LedgerController::class, 'viewPdf'])->name('finance.reports.view.pdf');
        Route::get('/reports/view/excel', [\App\Http\Controllers\Finance\LedgerController::class, 'viewExcel'])->name('finance.reports.view.excel');
        Route::get('/reports/export/pdf', [\App\Http\Controllers\Finance\LedgerController::class, 'exportPdf'])->name('finance.reports.export.pdf');
        Route::get('/reports/export/excel', [\App\Http\Controllers\Finance\LedgerController::class, 'exportExcel'])->name('finance.reports.export.excel');
    });

    Route::prefix('administration')->middleware(['permission:administration.read'])->group(function () use ($registerModuleBudgeting) {
        Route::get('/', [\App\Http\Controllers\Administration\DashboardController::class, '__invoke'])->name('administration.dashboard');
        Route::get('/sidebar-notifications', \App\Http\Controllers\Administration\SidebarNotificationController::class)->name('administration.sidebar-notifications');
        $registerModuleBudgeting('administration');

        Route::get('/planning', [\App\Http\Controllers\Administration\PlanningController::class, 'index'])->name('administration.planning.index');
        Route::post('/planning', [\App\Http\Controllers\Administration\PlanningController::class, 'store'])->name('administration.planning.store');
        Route::post('/planning/{cycle}/lock', [\App\Http\Controllers\Administration\PlanningController::class, 'lock'])->name('administration.planning.lock');

        Route::get('/workflow', [\App\Http\Controllers\Administration\WorkflowController::class, 'index'])->name('administration.workflow.index');
        Route::post('/workflow/calendar-events', [\App\Http\Controllers\Administration\WorkflowController::class, 'storeEvent'])->name('administration.workflow.calendar.store');
        Route::post('/workflow/tasks', [\App\Http\Controllers\Administration\WorkflowController::class, 'storeTask'])->name('administration.workflow.tasks.store');
        Route::post('/workflow/tasks/{task}/complete', [\App\Http\Controllers\Administration\WorkflowController::class, 'completeTask'])->name('administration.workflow.tasks.complete');
        Route::post('/workflow/variances', [\App\Http\Controllers\Administration\WorkflowController::class, 'storeVariance'])->name('administration.workflow.variances.store');

        Route::get('/budget-aggregation', [\App\Http\Controllers\Administration\BudgetAggregationController::class, 'index'])->name('administration.budget-aggregation.index');
        Route::post('/budget-aggregation', [\App\Http\Controllers\Administration\BudgetAggregationController::class, 'store'])->name('administration.budget-aggregation.store');

        Route::get('/approvals', [\App\Http\Controllers\Administration\ApprovalWorkflowController::class, 'index'])->name('administration.approvals.index');
        Route::get('/approvals/{budget_request}', [\App\Http\Controllers\Administration\ApprovalWorkflowController::class, 'show'])->name('administration.approvals.show');
        Route::post('/approvals/{budget_request}/review', [\App\Http\Controllers\Administration\ApprovalWorkflowController::class, 'review'])->name('administration.approvals.review');
        Route::post('/approvals/{budget_request}/route-finance', [\App\Http\Controllers\Administration\ApprovalWorkflowController::class, 'routeToFinance'])->name('administration.approvals.route-finance');
        Route::post('/approvals/{budget_request}/return', [\App\Http\Controllers\Administration\ApprovalWorkflowController::class, 'returnToSender'])->name('administration.approvals.return');
        Route::post('/approvals/{budget_request}/reject', [\App\Http\Controllers\Administration\ApprovalWorkflowController::class, 'reject'])->name('administration.approvals.reject');

        Route::get('/fund-distribution', [\App\Http\Controllers\Administration\FundDistributionController::class, 'index'])->name('administration.fund-distribution.index');
        Route::post('/fund-distribution', [\App\Http\Controllers\Administration\FundDistributionController::class, 'store'])->name('administration.fund-distribution.store');
        Route::post('/fund-distribution/budget-requests/{id}/disburse', [\App\Http\Controllers\Administration\FundDistributionController::class, 'markAsDisbursed'])->name('administration.fund-distribution.budget.disburse');
        Route::post('/fund-distribution/allocations/{allocation}/disburse', [\App\Http\Controllers\Administration\FundDistributionController::class, 'markAllocationAsDisbursed'])->name('administration.fund-distribution.disburse');

        Route::get('/applications', [\App\Http\Controllers\Administration\AdmissionsOpsController::class, 'applications'])->name('administration.applications.index');
        Route::get('/lifecycle', [\App\Http\Controllers\Administration\AdmissionsOpsController::class, 'lifecycle'])->name('administration.lifecycle.index');
        Route::get('/admission-packages', [\App\Http\Controllers\Administration\AdmissionsOpsController::class, 'packages'])->name('administration.admission-packages.index');
        Route::post('/admission-packages/letter', [\App\Http\Controllers\Administration\AdmissionsOpsController::class, 'storeAdmissionLetter'])->name('administration.admission-packages.letter.store');
        Route::get('/admission-packages/letter/download', [\App\Http\Controllers\Administration\AdmissionsOpsController::class, 'downloadAdmissionLetter'])->name('administration.admission-packages.letter.download');
        Route::delete('/admission-packages/letter', [\App\Http\Controllers\Administration\AdmissionsOpsController::class, 'destroyAdmissionLetter'])->name('administration.admission-packages.letter.destroy');

        Route::get('/statutory', [\App\Http\Controllers\Administration\ComplianceController::class, 'statutory'])->name('administration.statutory.index');
        Route::post('/statutory', [\App\Http\Controllers\Administration\ComplianceController::class, 'storeStatutory'])->name('administration.statutory.store');
        Route::get('/statutory/{certification}/download', [\App\Http\Controllers\Administration\ComplianceController::class, 'downloadStatutory'])->name('administration.statutory.download');
        Route::get('/inspection', [\App\Http\Controllers\Administration\ComplianceController::class, 'inspection'])->name('administration.inspection.index');
        Route::post('/inspection', [\App\Http\Controllers\Administration\ComplianceController::class, 'storeInspectionCheck'])->name('administration.inspection.store');
        Route::post('/inspection/{check}/status', [\App\Http\Controllers\Administration\ComplianceController::class, 'updateInspectionStatus'])->name('administration.inspection.status');

        Route::get('/procurement-pay', [\App\Http\Controllers\Administration\ProcurementLedgerController::class, 'procurementPay'])->name('administration.procurement-pay.index');
        Route::get('/ledger-sync', [\App\Http\Controllers\Administration\ProcurementLedgerController::class, 'ledgerSync'])->name('administration.ledger-sync.index');
        Route::post('/ledger-sync/run', [\App\Http\Controllers\Administration\ProcurementLedgerController::class, 'runSync'])->name('administration.ledger-sync.run');
    });

    Route::prefix('qa')->middleware(['permission:qa.read'])->group(function () use ($registerModuleBudgeting) {
        Route::get('/', [\App\Http\Controllers\Qa\DashboardController::class, '__invoke'])->name('qa.dashboard');
        $registerModuleBudgeting('qa');
    });

    Route::prefix('procurement')->middleware(['permission:procurement.read'])->group(function () use ($registerModuleBudgeting) {
        Route::get('/', [\App\Http\Controllers\Procurement\DashboardController::class, '__invoke'])->name('procurement.dashboard');
        $registerModuleBudgeting('procurement');
    });

    Route::prefix('research')->middleware(['permission:research.read'])->group(function () use ($registerModuleBudgeting) {
        Route::get('/', [\App\Http\Controllers\Research\DashboardController::class, '__invoke'])->name('research.dashboard');
        $registerModuleBudgeting('research');
    });

    Route::prefix('ict')->middleware(['permission:ict.read'])->group(function () use ($registerModuleBudgeting) {
        Route::get('/', [\App\Http\Controllers\Ict\DashboardController::class, '__invoke'])->name('ict.dashboard');
        $registerModuleBudgeting('ict');
        Route::get('/registration-invites', [\App\Http\Controllers\Ict\RegistrationInviteController::class, 'index'])->name('ict.registration-invites.index');
        Route::post('/registration-invites', [\App\Http\Controllers\Ict\RegistrationInviteController::class, 'store'])->name('ict.registration-invites.store');

        Route::middleware(['permission:users.access.manage'])->group(function () {
            Route::get('/users', [\App\Http\Controllers\Ict\UserAccessController::class, 'index'])->name('ict.users.index');
            Route::put('/users/{user}/access', [\App\Http\Controllers\Ict\UserAccessController::class, 'update'])->name('ict.users.update');

            Route::get('/roles', [\App\Http\Controllers\Ict\RoleController::class, 'index'])->name('ict.roles.index');
            Route::post('/roles', [\App\Http\Controllers\Ict\RoleController::class, 'store'])->name('ict.roles.store');
            Route::put('/roles/{role}', [\App\Http\Controllers\Ict\RoleController::class, 'update'])->name('ict.roles.update');
            Route::put('/roles/{role}/permissions', [\App\Http\Controllers\Ict\RoleController::class, 'updatePermissions'])->name('ict.roles.permissions.update');
            Route::delete('/roles/{role}', [\App\Http\Controllers\Ict\RoleController::class, 'destroy'])->name('ict.roles.destroy');

            Route::get('/role-categories', [\App\Http\Controllers\Ict\RoleCategoryController::class, 'index'])->name('ict.role-categories.index');
            Route::post('/role-categories/reorder', [\App\Http\Controllers\Ict\RoleCategoryController::class, 'reorder'])->name('ict.role-categories.reorder');
            Route::post('/role-categories', [\App\Http\Controllers\Ict\RoleCategoryController::class, 'store'])->name('ict.role-categories.store');
            Route::put('/role-categories/{roleCategory}', [\App\Http\Controllers\Ict\RoleCategoryController::class, 'update'])->name('ict.role-categories.update');
            Route::delete('/role-categories/{roleCategory}', [\App\Http\Controllers\Ict\RoleCategoryController::class, 'destroy'])->name('ict.role-categories.destroy');
        });
    });

    Route::prefix('hr')->middleware(['permission:hr.staff.view'])->group(function () use ($registerModuleBudgeting) {
        Route::get('/', [\App\Http\Controllers\HR\DashboardController::class, '__invoke'])->name('hr.dashboard');
        Route::get('/sidebar-notifications', \App\Http\Controllers\HR\SidebarNotificationController::class)->name('hr.sidebar-notifications');
        $registerModuleBudgeting('hr');
        Route::post('/registration-invites', [\App\Http\Controllers\HR\RegistrationInviteController::class, 'store'])->name('hr.registration-invites.store');

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
            Route::get('/leave/carry-forward', [\App\Http\Controllers\HR\LeaveCarryForwardController::class, 'index'])
                ->middleware('permission:hr.manage_leave')
                ->name('hr.leave.carry-forward.index');
            Route::post('/leave/carry-forward/{carryForwardRequest}/approve', [\App\Http\Controllers\HR\LeaveCarryForwardController::class, 'approve'])
                ->middleware('permission:hr.manage_leave')
                ->name('hr.leave.carry-forward.approve');
            Route::post('/leave/carry-forward/{carryForwardRequest}/reject', [\App\Http\Controllers\HR\LeaveCarryForwardController::class, 'reject'])
                ->middleware('permission:hr.manage_leave')
                ->name('hr.leave.carry-forward.reject');
            Route::get('/payroll', [\App\Http\Controllers\HR\PayrollController::class, 'index'])->name('hr.payroll.index');
            Route::get('/payroll/report', [\App\Http\Controllers\HR\PayrollController::class, 'report'])->name('hr.payroll.report');
            Route::get('/payroll/report/pdf', [\App\Http\Controllers\HR\PayrollController::class, 'reportPdf'])->name('hr.payroll.report.pdf');
            Route::get('/payroll/runs', [\App\Http\Controllers\HR\PayrollRunController::class, 'index'])->name('hr.payroll.runs.index');
            Route::get('/payroll/runs/create', [\App\Http\Controllers\HR\PayrollRunController::class, 'create'])->name('hr.payroll.runs.create');
            Route::post('/payroll/runs', [\App\Http\Controllers\HR\PayrollRunController::class, 'store'])->name('hr.payroll.runs.store');
            Route::get('/payroll/runs/{payrollRun}', [\App\Http\Controllers\HR\PayrollRunController::class, 'show'])->name('hr.payroll.runs.show');
            Route::post('/payroll/runs/{payrollRun}/recalculate', [\App\Http\Controllers\HR\PayrollRunController::class, 'recalculate'])->name('hr.payroll.runs.recalculate');
            Route::post('/payroll/runs/{payrollRun}/approve', [\App\Http\Controllers\HR\PayrollRunController::class, 'approve'])->middleware('permission:hr.manage_contracts')->name('hr.payroll.runs.approve');
            Route::post('/payroll/runs/{payrollRun}/cancel', [\App\Http\Controllers\HR\PayrollRunController::class, 'cancel'])->name('hr.payroll.runs.cancel');
            Route::get('/payroll/runs/{payrollRun}/statutory/{agency}', [\App\Http\Controllers\HR\PayrollRunController::class, 'exportStatutory'])->name('hr.payroll.runs.statutory.export');
            Route::get('/payroll/items/{payrollItem}/payslip', [\App\Http\Controllers\HR\PayrollRunController::class, 'itemPayslip'])->name('hr.payroll.runs.item.payslip');
            Route::get('/payroll/items/{payrollItem}/payslip/pdf', [\App\Http\Controllers\HR\PayrollRunController::class, 'itemPayslipPdf'])->name('hr.payroll.runs.item.payslip.pdf');
            Route::redirect('payroll/tax', 'payroll');
            Route::redirect('payroll/tax/settings', 'payroll/settings');
            Route::middleware('permission:hr.manage_contracts')->group(function () {
                Route::get('/payroll/settings', [\App\Http\Controllers\HR\PayrollController::class, 'settings'])->name('hr.payroll.settings');
                Route::put('/payroll/settings', [\App\Http\Controllers\HR\PayrollController::class, 'updateSettings'])->name('hr.payroll.settings.update');
            });
            Route::get('/p9-forms', [\App\Http\Controllers\HR\P9FormController::class, 'index'])->name('hr.p9-forms.index');
            Route::get('/p9-forms/{staff}', [\App\Http\Controllers\HR\P9FormController::class, 'show'])->name('hr.p9-forms.show');
            Route::get('/p9-forms/{staff}/download', [\App\Http\Controllers\HR\P9FormController::class, 'download'])->name('hr.p9-forms.download');
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
            Route::get('/attendance', [\App\Http\Controllers\HR\AttendanceReviewController::class, 'index'])->name('hr.attendance.index');
            Route::get('/attendance/{attendance}', [\App\Http\Controllers\HR\AttendanceReviewController::class, 'show'])->name('hr.attendance.show');
            Route::post('/attendance/{attendance}/approve', [\App\Http\Controllers\HR\AttendanceReviewController::class, 'approve'])->name('hr.attendance.approve');
            Route::post('/attendance/{attendance}/reject', [\App\Http\Controllers\HR\AttendanceReviewController::class, 'reject'])->name('hr.attendance.reject');

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

    Route::prefix('finance/api')
        ->middleware(['permission:finance.read'])
        ->name('finance.api.')
        ->group(function () {
            Route::get('/programs', [\App\Http\Controllers\Finance\StudentFinance\StudentFinanceController::class, 'apiPrograms'])->name('programs');
            Route::get('/academic-years', [\App\Http\Controllers\Finance\StudentFinance\StudentFinanceController::class, 'apiAcademicYears'])->name('academic-years');
            Route::get('/students', [\App\Http\Controllers\Finance\StudentFinance\StudentFinanceController::class, 'apiStudents'])->name('students');
            Route::get('/invoices', [\App\Http\Controllers\Finance\StudentFinance\StudentFinanceController::class, 'apiInvoices'])->name('invoices');
            Route::get('/suppliers', [\App\Http\Controllers\Finance\FinanceController::class, 'apiSuppliers'])->name('suppliers');
        });

    Route::prefix('departments/{department}/finance')
        ->where(['department' => '[0-9]+(-[0-9]+)?'])
        ->middleware(['permission:finance.read'])
        ->name('finance.')
        ->group(function () {
            Route::get('/student-finance', [\App\Http\Controllers\Finance\StudentFinance\StudentFinanceController::class, 'index'])->name('student-finance.index');
            Route::get('/student-finance/accounts', [\App\Http\Controllers\Finance\StudentFinance\StudentFinanceController::class, 'accounts'])->name('student-finance.accounts.index');
            Route::get('/student-finance/accounts/{id}', [\App\Http\Controllers\Finance\StudentFinance\StudentFinanceController::class, 'accountShow'])->name('student-finance.accounts.show');
            Route::get('/student-finance/fee-structures', [\App\Http\Controllers\Finance\StudentFinance\StudentFinanceController::class, 'feeStructures'])->name('student-finance.fee-structures.index');
            Route::get('/student-finance/fee-structures/create', [\App\Http\Controllers\Finance\StudentFinance\StudentFinanceController::class, 'feeStructureCreate'])->name('student-finance.fee-structures.create');
            Route::post('/student-finance/fee-structures', [\App\Http\Controllers\Finance\StudentFinance\StudentFinanceController::class, 'feeStructureStore'])->name('student-finance.fee-structures.store');
            Route::get('/student-finance/fee-structures/{id}', [\App\Http\Controllers\Finance\StudentFinance\StudentFinanceController::class, 'feeStructureShow'])->name('student-finance.fee-structures.show');
            Route::get('/student-finance/invoices', [\App\Http\Controllers\Finance\StudentFinance\StudentFinanceController::class, 'invoices'])->name('student-finance.invoices.index');
            Route::get('/student-finance/invoices/create', [\App\Http\Controllers\Finance\StudentFinance\StudentFinanceController::class, 'invoiceCreate'])->name('student-finance.invoices.create');
            Route::post('/student-finance/invoices', [\App\Http\Controllers\Finance\StudentFinance\StudentFinanceController::class, 'invoiceStore'])->name('student-finance.invoices.store');
            Route::get('/student-finance/invoices/{id}', [\App\Http\Controllers\Finance\StudentFinance\StudentFinanceController::class, 'invoiceShow'])->name('student-finance.invoices.show');
            Route::get('/student-finance/invoices/{id}/download', [\App\Http\Controllers\Finance\StudentFinance\StudentFinanceController::class, 'invoiceDownload'])->name('student-finance.invoices.download');
            Route::get('/student-finance/payments', [\App\Http\Controllers\Finance\StudentFinance\StudentFinanceController::class, 'payments'])->name('student-finance.payments.index');
            Route::get('/student-finance/payments/{id}', [\App\Http\Controllers\Finance\StudentFinance\StudentFinanceController::class, 'paymentShow'])->name('student-finance.payments.show');
            Route::get('/student-finance/receipts', [\App\Http\Controllers\Finance\StudentFinance\StudentFinanceController::class, 'receipts'])->name('student-finance.receipts.index');
            Route::get('/student-finance/receipts/{id}', [\App\Http\Controllers\Finance\StudentFinance\StudentFinanceController::class, 'receiptShow'])->name('student-finance.receipts.show');
            Route::get('/student-finance/receipts/{id}/download', [\App\Http\Controllers\Finance\StudentFinance\StudentFinanceController::class, 'receiptDownload'])->name('student-finance.receipts.download');
            Route::get('/student-finance/adjustments', [\App\Http\Controllers\Finance\StudentFinance\StudentFinanceController::class, 'adjustments'])->name('student-finance.adjustments.index');
            Route::get('/student-finance/adjustments/create', [\App\Http\Controllers\Finance\StudentFinance\StudentFinanceController::class, 'adjustmentCreate'])->name('student-finance.adjustments.create');
            Route::post('/student-finance/adjustments', [\App\Http\Controllers\Finance\StudentFinance\StudentFinanceController::class, 'adjustmentStore'])->name('student-finance.adjustments.store');
            Route::get('/student-finance/adjustments/{id}', [\App\Http\Controllers\Finance\StudentFinance\StudentFinanceController::class, 'adjustmentShow'])->name('student-finance.adjustments.show');
            Route::post('/student-finance/adjustments/{id}/approve', [\App\Http\Controllers\Finance\StudentFinance\StudentFinanceController::class, 'adjustmentApprove'])->name('student-finance.adjustments.approve');
            Route::get('/student-finance/installment-plans', [\App\Http\Controllers\Finance\StudentFinance\StudentFinanceController::class, 'installmentPlans'])->name('student-finance.installment-plans.index');
            Route::get('/student-finance/installment-plans/create', [\App\Http\Controllers\Finance\StudentFinance\StudentFinanceController::class, 'installmentPlanCreate'])->name('student-finance.installment-plans.create');
            Route::post('/student-finance/installment-plans', [\App\Http\Controllers\Finance\StudentFinance\StudentFinanceController::class, 'installmentPlanStore'])->name('student-finance.installment-plans.store');
            Route::get('/student-finance/installment-plans/{id}', [\App\Http\Controllers\Finance\StudentFinance\StudentFinanceController::class, 'installmentPlanShow'])->name('student-finance.installment-plans.show');
            Route::get('/student-finance/clearance', [\App\Http\Controllers\Finance\StudentFinance\StudentFinanceController::class, 'clearance'])->name('student-finance.clearance.index');
            Route::post('/student-finance/clearance/{id}/approve', [\App\Http\Controllers\Finance\StudentFinance\StudentFinanceController::class, 'clearanceApprove'])->name('student-finance.clearance.approve');
            Route::post('/student-finance/clearance/{id}/reject', [\App\Http\Controllers\Finance\StudentFinance\StudentFinanceController::class, 'clearanceReject'])->name('student-finance.clearance.reject');
            Route::get('/student-finance/milestones', [\App\Http\Controllers\Finance\StudentFinance\StudentFinanceController::class, 'milestones'])->name('student-finance.milestones.index');
            Route::get('/student-finance/milestones/{id}', [\App\Http\Controllers\Finance\StudentFinance\StudentFinanceController::class, 'milestoneShow'])->name('student-finance.milestones.show');

            Route::get('/ar', [\App\Http\Controllers\Finance\AccountsReceivableController::class, 'index'])->name('ar.index');
            Route::get('/ar/aging/export/pdf', [\App\Http\Controllers\Finance\AccountsReceivableController::class, 'exportAgingPdf'])->name('ar.aging.export.pdf');
            Route::post('/ar/remind-bulk', [\App\Http\Controllers\Finance\AccountsReceivableController::class, 'sendBulkReminders'])->name('ar.remind.bulk');
            Route::post('/ar/invoices/{invoice}/remind', [\App\Http\Controllers\Finance\AccountsReceivableController::class, 'sendReminder'])->name('ar.invoices.remind');
            Route::post('/ar/statements/export/pdf', [\App\Http\Controllers\Finance\AccountsReceivableController::class, 'exportStatementsPdf'])->name('ar.statements.export.pdf');
            Route::get('/ar/credit-memos', [\App\Http\Controllers\Finance\AccountsReceivableController::class, 'creditMemosIndex'])->name('ar.credit-memos.index');
            Route::get('/ar/credit-memos/create', [\App\Http\Controllers\Finance\AccountsReceivableController::class, 'creditMemoCreate'])->name('ar.credit-memos.create');
            Route::post('/ar/credit-memos', [\App\Http\Controllers\Finance\AccountsReceivableController::class, 'creditMemoStore'])->name('ar.credit-memos.store');
            Route::get('/ar/credit-memos/{creditMemo}', [\App\Http\Controllers\Finance\AccountsReceivableController::class, 'creditMemoShow'])->name('ar.credit-memos.show');

            Route::get('/ap', [\App\Http\Controllers\Finance\FinanceController::class, 'apIndex'])->name('ap.index');
            Route::get('/ap/create', [\App\Http\Controllers\Finance\FinanceController::class, 'apCreate'])->name('ap.create');
            Route::post('/ap', [\App\Http\Controllers\Finance\FinanceController::class, 'apStore'])->name('ap.store');
            Route::get('/ap/{ap}', [\App\Http\Controllers\Finance\FinanceController::class, 'apShow'])->name('ap.show');
            Route::get('/suppliers', [\App\Http\Controllers\Finance\FinanceController::class, 'suppliersWorkflow'])->name('suppliers.index');

            Route::get('/gl', [\App\Http\Controllers\Finance\FinanceController::class, 'glIndex'])->name('gl.index');
            Route::get('/gl/journal/create', [\App\Http\Controllers\Finance\FinanceController::class, 'glJournalCreate'])->name('gl.journal.create');
            Route::post('/gl/journal', [\App\Http\Controllers\Finance\FinanceController::class, 'glJournalStore'])->name('gl.journal.store');
            Route::get('/gl/{gl}', [\App\Http\Controllers\Finance\FinanceController::class, 'glShow'])->name('gl.show');

            Route::get('/budgeting', [\App\Http\Controllers\Finance\FinanceController::class, 'budgetingIndex'])->name('budgeting.index');
            Route::get('/budgeting/create', [\App\Http\Controllers\Finance\FinanceController::class, 'budgetingCreate'])->name('budgeting.create');
            Route::post('/budgeting', [\App\Http\Controllers\Finance\FinanceController::class, 'budgetingStore'])->name('budgeting.store');

            Route::get('/budgeting/requests', [\App\Http\Controllers\Finance\FinanceController::class, 'budgetingRequestsIndex'])->name('budgeting.requests.index');
            Route::get('/budgeting/requests/{id}', [\App\Http\Controllers\Finance\FinanceController::class, 'budgetingRequestShow'])->name('budgeting.requests.show');
            Route::post('/budgeting/requests/{id}/review', [\App\Http\Controllers\Finance\FinanceController::class, 'budgetingRequestReview'])->name('budgeting.requests.review');
            Route::post('/budgeting/requests/{id}/reject', [\App\Http\Controllers\Finance\FinanceController::class, 'budgetingRequestReject'])->name('budgeting.requests.reject');
            Route::post('/budgeting/requests/{id}/ceo-approve', [\App\Http\Controllers\Finance\FinanceController::class, 'ceoApprove'])->name('budgeting.requests.ceo-approve');
            Route::post('/budgeting/requests/{id}/disburse', [\App\Http\Controllers\Finance\FinanceController::class, 'markAsDisbursed'])->name('budgeting.requests.disburse');

            Route::get('/budgeting/{budgeting}', [\App\Http\Controllers\Finance\FinanceController::class, 'budgetingShow'])->name('budgeting.show');
            Route::post('/budgeting/{budget}/cycles', [\App\Http\Controllers\Finance\FinanceController::class, 'budgetingCycleStore'])->name('budgeting.cycles.store');
            Route::put('/budgeting/{budget}/cycles/{cycle}', [\App\Http\Controllers\Finance\FinanceController::class, 'budgetingCycleUpdate'])->name('budgeting.cycles.update');
            Route::delete('/budgeting/{budget}/cycles/{cycle}', [\App\Http\Controllers\Finance\FinanceController::class, 'budgetingCycleDestroy'])->name('budgeting.cycles.destroy');

            Route::get('/projects-donors', [\App\Http\Controllers\Finance\FinanceController::class, 'projectsDonorsIndex'])->name('projects-donors.index');
            Route::get('/projects-donors/create', [\App\Http\Controllers\Finance\FinanceController::class, 'projectsDonorsCreate'])->name('projects-donors.create');
            Route::post('/projects-donors', [\App\Http\Controllers\Finance\FinanceController::class, 'projectsDonorsStore'])->name('projects-donors.store');
            Route::get('/projects-donors/{projectDonor}', [\App\Http\Controllers\Finance\FinanceController::class, 'projectsDonorsShow'])->name('projects-donors.show');

            Route::get('/payroll-integration', [\App\Http\Controllers\Finance\PayrollIntegrationController::class, 'index'])->name('payroll-integration.index');
            Route::get('/payroll-integration/{payrollRun}', [\App\Http\Controllers\Finance\PayrollIntegrationController::class, 'show'])->name('payroll-integration.show');
            Route::post('/payroll-integration/{payrollRun}/post', [\App\Http\Controllers\Finance\PayrollIntegrationController::class, 'post'])->name('payroll-integration.post');
        });

    $registerAcademicsRoutes = require __DIR__.'/includes/academics.php';

    Route::prefix('academics')
        ->middleware('resolve.academics.hub')
        ->group(function () use ($registerAcademicsRoutes, $registerModuleBudgeting) {
            $registerAcademicsRoutes(true);
            Route::middleware('permission:academics.read')->group(function () use ($registerModuleBudgeting) {
                $registerModuleBudgeting('academics');
            });
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
        Route::get('/sidebar-notifications', \App\Http\Controllers\Portal\SidebarNotificationController::class)->name('portal.sidebar-notifications');

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
        Route::post('/invoices/{invoice}/pay', [\App\Http\Controllers\Portal\FinancePaymentController::class, 'store'])
            ->name('portal.invoices.pay');
        Route::get('/mpesa/stk/{stkRequest}/status', [\App\Http\Controllers\Portal\MpesaPaymentStatusController::class, '__invoke'])
            ->name('portal.mpesa.stk.status');
    });

    Route::get('/lesson-plans/{plan}/print', [StaffLessonPlanDocumentController::class, 'print'])->name('lesson-plans.print');
    Route::get('/lesson-plans/{plan}/pdf', [StaffLessonPlanDocumentController::class, 'pdf'])->name('lesson-plans.pdf');
    Route::get('/lesson-plans/{plan}/upload', [StaffLessonPlanDocumentController::class, 'showUpload'])->name('lesson-plans.upload.show');
    Route::get('/lesson-plans/{plan}/upload/download', [StaffLessonPlanDocumentController::class, 'downloadUpload'])->name('lesson-plans.upload.download');

    Route::get('/employee', [\App\Http\Controllers\Employee\EmployeePortalController::class, '__invoke'])
        ->middleware('employee.portal')
        ->name('employee.dashboard');

    Route::middleware('employee.portal')->prefix('employee')->group(function () {
        Route::get('/sidebar-notifications', \App\Http\Controllers\Employee\SidebarNotificationController::class)->name('employee.sidebar-notifications');

        Route::get('/profile/edit', [\App\Http\Controllers\Employee\EmployeeProfileController::class, 'edit'])->name('employee.profile.edit');
        Route::post('/profile', [\App\Http\Controllers\Employee\EmployeeProfileController::class, 'update'])->name('employee.profile.update');

        Route::get('/leave', [\App\Http\Controllers\Employee\EmployeeLeaveController::class, 'index'])->name('employee.leave.index');
        Route::post('/leave', [\App\Http\Controllers\Employee\EmployeeLeaveController::class, 'store'])->name('employee.leave.store');
        Route::put('/leave/{leaveRequest}', [\App\Http\Controllers\Employee\EmployeeLeaveController::class, 'update'])->name('employee.leave.update');
        Route::post('/leave/{leaveRequest}/cancel', [\App\Http\Controllers\Employee\EmployeeLeaveController::class, 'cancel'])->name('employee.leave.cancel');
        Route::get('/leave/carry-forward', [\App\Http\Controllers\Employee\EmployeeLeaveController::class, 'carryForwardForm'])->name('employee.leave.carry-forward');
        Route::post('/leave/carry-forward', [\App\Http\Controllers\Employee\EmployeeLeaveController::class, 'carryForwardStore'])->name('employee.leave.carry-forward.store');

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
        Route::get('/sidebar-notifications', \App\Http\Controllers\Staff\SidebarNotificationController::class)->name('staff.sidebar-notifications');

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
    Route::get('/pay', [ApplicationPaymentController::class, 'show'])->name('apply.pay');
    Route::post('/pay', [ApplicationPaymentController::class, 'store'])->name('apply.pay.store');
    Route::get('/pay/stk/{stkRequest}/status', [ApplicationPaymentController::class, 'status'])->name('apply.pay.stk.status');
    Route::post('/reset', [ApplicationController::class, 'reset'])->name('apply.reset');
});

Route::get('/careers', [\App\Http\Controllers\Public\CareerController::class, 'index'])->name('careers.index');
Route::get('/careers/{vacancy}', [\App\Http\Controllers\Public\CareerController::class, 'show'])->name('careers.show');

Route::post('/webhooks/mpesa/stk-callback', \App\Http\Controllers\Webhooks\MpesaStkCallbackController::class)
    ->name('webhooks.mpesa.stk-callback');

Route::prefix('vacancies')->group(function () {
    Route::get('/apply/{vacancy}', [\App\Http\Controllers\Public\VacancyApplicationController::class, 'create'])->name('vacancies.apply.create');
    Route::post('/apply/{vacancy}', [\App\Http\Controllers\Public\VacancyApplicationController::class, 'store'])->name('vacancies.apply.store');
    Route::get('/apply/confirmation/{application}', [\App\Http\Controllers\Public\VacancyApplicationController::class, 'confirmation'])->name('vacancies.apply.confirmation');
    Route::match(['get', 'post'], '/track', [\App\Http\Controllers\Public\VacancyApplicationController::class, 'track'])->name('vacancies.track');
});
