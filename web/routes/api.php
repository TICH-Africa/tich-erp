<?php

use App\Http\Controllers\AuditController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Auth\MFAController;
use App\Http\Controllers\HR\AllowanceController;
use App\Http\Controllers\HR\ContractController;
use App\Http\Controllers\HR\DocumentController;
use App\Http\Controllers\HR\OnboardingController;
use App\Http\Controllers\HR\StaffController;
use App\Http\Controllers\HR\StatusHistoryController;
use App\Http\Controllers\HR\VacancyController;
use App\Http\Controllers\RBACController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/mfa/challenge', [AuthController::class, 'mfaChallenge'])->middleware('throttle:mfa');
        Route::post('/mfa/setup', [AuthController::class, 'mfaSetup'])->middleware('throttle:mfa');
        Route::post('/mfa/setup/verify', [AuthController::class, 'mfaSetupVerify'])->middleware('throttle:mfa');
        Route::post('/mfa/disable', [AuthController::class, 'mfaDisable']);
        Route::post('/change-password', [AuthController::class, 'changePassword']);
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/device-token', [\App\Http\Controllers\Api\DeviceTokenController::class, 'store']);
        Route::delete('/device-token', [\App\Http\Controllers\Api\DeviceTokenController::class, 'destroy']);

        Route::prefix('mfa')->middleware('throttle:mfa')->group(function () {
            Route::get('/status', [MFAController::class, 'getMFAStatus']);
            Route::post('/email/send', [MFAController::class, 'sendEmailOTP']);
            Route::post('/email/verify', [MFAController::class, 'verifyEmailOTP']);
            Route::post('/totp/setup', [MFAController::class, 'setupTOTP']);
            Route::post('/totp/enable', [MFAController::class, 'enableTOTP']);
            Route::post('/totp/verify', [MFAController::class, 'verifyTOTP']);
            Route::post('/backup/verify', [MFAController::class, 'verifyBackupCode']);
            Route::post('/disable', [MFAController::class, 'disableMFA']);
            Route::post('/backup/regenerate', [MFAController::class, 'regenerateBackupCodes']);
        });
    });
});

Route::middleware(['auth:sanctum', 'mfa'])->prefix('rbac')->group(function () {
    Route::get('/permissions', [RBACController::class, 'getUserPermissions']);
    Route::get('/roles', [RBACController::class, 'getUserRoles']);
    Route::post('/roles/assign', [RBACController::class, 'assignRole']);
    Route::post('/roles/revoke', [RBACController::class, 'revokeRole']);
    Route::post('/permissions/assign', [RBACController::class, 'assignPermission']);
    Route::post('/permissions/revoke', [RBACController::class, 'revokePermission']);
    Route::get('/catalog/roles', [RBACController::class, 'getRoles']);
    Route::get('/catalog/permissions', [RBACController::class, 'getPermissions']);
    Route::get('/catalog/role-permissions', [RBACController::class, 'getRolePermissions']);
    Route::post('/catalog/role-permissions/sync', [RBACController::class, 'assignPermissionsToRole']);
});

Route::middleware(['auth:sanctum', 'mfa', 'permission:audit_logs.read'])->prefix('admin')->group(function () {
    Route::get('/audit-logs/verify', [AuditController::class, 'verifyChain']);
    Route::get('/audit-logs/export', [AuditController::class, 'export']);
    Route::get('/audit-logs/{id}', [AuditController::class, 'show']);
    Route::get('/audit-logs', [AuditController::class, 'index']);
});

Route::middleware(['auth:sanctum', 'mfa'])->prefix('hr')->group(function () {
    Route::middleware('permission:hr.staff.view')->group(function () {
        Route::get('/staff', [StaffController::class, 'index']);
        Route::get('/staff/{staff}', [StaffController::class, 'show']);
        Route::get('/staff/{staff}/onboarding', [OnboardingController::class, 'index']);
        Route::get('/staff/{staff}/documents', [DocumentController::class, 'index']);
        Route::get('/staff/{staff}/allowances', [AllowanceController::class, 'index']);
        Route::get('/staff/{staff}/status-history', [StatusHistoryController::class, 'index']);
    });

    Route::middleware('permission:hr.staff.create')->group(function () {
        Route::post('/staff', [StaffController::class, 'store']);
        Route::post('/staff/{staff}/documents', [DocumentController::class, 'store']);
        Route::post('/staff/{staff}/allowances', [AllowanceController::class, 'store']);
        Route::post('/staff/{staff}/status-history', [StatusHistoryController::class, 'store']);
    });

    Route::middleware('permission:hr.staff.edit')->group(function () {
        Route::put('/staff/{staff}', [StaffController::class, 'update']);
        Route::put('/staff/{staff}/onboarding/{onboarding}/step', [OnboardingController::class, 'updateStep']);
        Route::put('/staff/{staff}/onboarding/{onboarding}/approve', [OnboardingController::class, 'approve']);
        Route::put('/staff/{staff}/onboarding/{onboarding}/reject', [OnboardingController::class, 'reject']);
        Route::put('/staff/{staff}/onboarding/{onboarding}/complete', [OnboardingController::class, 'complete']);
        Route::post('/staff/{staff}/documents/{document}/verify', [DocumentController::class, 'verify']);
    });

    Route::middleware('permission:hr.staff.delete')->group(function () {
        Route::delete('/staff/{staff}', [StaffController::class, 'destroy']);
        Route::delete('/staff/{staff}/documents/{document}', [DocumentController::class, 'destroy']);
    });

    Route::middleware('permission:hr.manage_contracts')->group(function () {
        Route::get('/contracts', [ContractController::class, 'index']);
        Route::get('/staff/{staff}/contracts', [ContractController::class, 'index']);
        Route::get('/contracts/{contract}', [ContractController::class, 'show']);
        Route::post('/staff/{staff}/contracts', [ContractController::class, 'store']);
        Route::put('/contracts/{contract}', [ContractController::class, 'update']);
        Route::delete('/contracts/{contract}', [ContractController::class, 'destroy']);
        Route::post('/contracts/{contract}/renew', [ContractController::class, 'renew']);
        Route::post('/contracts/{contract}/terminate', [ContractController::class, 'terminate']);
        Route::post('/contracts/{contract}/sign', [ContractController::class, 'sign']);
        Route::post('/contracts/{contract}/convert-permanent', [ContractController::class, 'convertToPermanent']);
        Route::get('/contracts/alerts', [ContractController::class, 'alerts']);
    });

    Route::middleware('permission:hr.manage_recruitment')->group(function () {
        Route::get('/vacancies', [VacancyController::class, 'index']);
        Route::post('/vacancies', [VacancyController::class, 'store']);
        Route::get('/vacancies/{vacancy}', [VacancyController::class, 'show']);
        Route::put('/vacancies/{vacancy}', [VacancyController::class, 'update']);
        Route::delete('/vacancies/{vacancy}', [VacancyController::class, 'destroy']);
        Route::post('/vacancies/{vacancy}/toggle-publish', [VacancyController::class, 'togglePublish']);
    });
});

Route::get('/session-check', function () {
    $authenticated = auth()->check();

    return response()->json([
        'authenticated' => $authenticated,
    ], 200);
})->middleware('web');

/*
| Mobile / Sanctum employee self-service portal.
| MFA is intentionally not required here (product: mobile v1).
*/
Route::middleware(['auth:sanctum', 'employee.portal'])->prefix('employee')->group(function () {
    Route::get('/dashboard', \App\Http\Controllers\Api\Employee\mobile\DashboardController::class);
    Route::get('/notifications', \App\Http\Controllers\Api\Employee\mobile\NotificationController::class);
    Route::post('/notifications/{notification}/read', [\App\Http\Controllers\Api\Employee\mobile\NotificationController::class, 'markRead']);

    Route::get('/profile', [\App\Http\Controllers\Api\Employee\mobile\ProfileController::class, 'show']);
    Route::put('/profile', [\App\Http\Controllers\Api\Employee\mobile\ProfileController::class, 'update']);

    Route::get('/leave', [\App\Http\Controllers\Api\Employee\mobile\LeaveController::class, 'index']);
    Route::post('/leave', [\App\Http\Controllers\Api\Employee\mobile\LeaveController::class, 'store']);
    Route::post('/leave/{leaveRequest}/cancel', [\App\Http\Controllers\Api\Employee\mobile\LeaveController::class, 'cancel']);
    Route::post('/leave/coverages/{coverage}/accept', [\App\Http\Controllers\Api\Employee\mobile\LeaveController::class, 'acceptCoverage']);
    Route::post('/leave/coverages/{coverage}/decline', [\App\Http\Controllers\Api\Employee\mobile\LeaveController::class, 'declineCoverage']);

    Route::get('/attendance', [\App\Http\Controllers\Api\Employee\mobile\AttendanceController::class, 'index']);
    Route::post('/attendance/clock-in', [\App\Http\Controllers\Api\Employee\mobile\AttendanceController::class, 'clockIn']);
    Route::post('/attendance/clock-out', [\App\Http\Controllers\Api\Employee\mobile\AttendanceController::class, 'clockOut']);

    Route::get('/documents', [\App\Http\Controllers\Api\Employee\mobile\DocumentController::class, 'index']);
    Route::post('/documents', [\App\Http\Controllers\Api\Employee\mobile\DocumentController::class, 'store']);
    Route::get('/documents/{document}/download', [\App\Http\Controllers\Api\Employee\mobile\DocumentController::class, 'download']);

    Route::get('/payslips', [\App\Http\Controllers\Api\Employee\mobile\PayslipController::class, 'index']);
    Route::get('/payslips/{payrollItem}', [\App\Http\Controllers\Api\Employee\mobile\PayslipController::class, 'show']);

    Route::get('/policies', [\App\Http\Controllers\Api\Employee\mobile\PolicyController::class, 'index']);
    Route::post('/policies/{id}/acknowledge', [\App\Http\Controllers\Api\Employee\mobile\PolicyController::class, 'acknowledge']);

    Route::get('/wfh', [\App\Http\Controllers\Api\Employee\mobile\PortalListsController::class, 'wfh']);
    Route::post('/wfh', [\App\Http\Controllers\Api\Employee\mobile\PortalListsController::class, 'storeWfh']);
    Route::get('/concerns', [\App\Http\Controllers\Api\Employee\mobile\PortalListsController::class, 'concerns']);
    Route::post('/concerns', [\App\Http\Controllers\Api\Employee\mobile\PortalListsController::class, 'storeConcern']);
    Route::get('/appraisals', [\App\Http\Controllers\Api\Employee\mobile\PortalListsController::class, 'appraisals']);
    Route::get('/time-logs', [\App\Http\Controllers\Api\Employee\mobile\TimeLogController::class, 'index']);
    Route::get('/time-logs/weeks', [\App\Http\Controllers\Api\Employee\mobile\TimeLogController::class, 'weeks']);
    Route::post('/time-logs', [\App\Http\Controllers\Api\Employee\mobile\TimeLogController::class, 'store']);
    Route::get('/time-logs/{timeLog}', [\App\Http\Controllers\Api\Employee\mobile\TimeLogController::class, 'show']);
    Route::put('/time-logs/{timeLog}', [\App\Http\Controllers\Api\Employee\mobile\TimeLogController::class, 'update']);
    Route::post('/time-logs/{timeLog}/submit', [\App\Http\Controllers\Api\Employee\mobile\TimeLogController::class, 'submit']);
    Route::get('/feedback', [\App\Http\Controllers\Api\Employee\mobile\PortalListsController::class, 'feedback']);
    Route::post('/feedback', [\App\Http\Controllers\Api\Employee\mobile\PortalListsController::class, 'storeFeedback']);
});

/*
| Mobile / Sanctum student self-service portal.
| MFA is intentionally not required here (product: mobile v1).
*/
Route::middleware(['auth:sanctum', 'student.portal'])->prefix('student')->group(function () {
    Route::get('/dashboard', \App\Http\Controllers\Api\Student\mobile\DashboardController::class);
    Route::get('/sidebar-notifications', \App\Http\Controllers\Api\Student\mobile\SidebarNotificationsController::class);
    Route::get('/academics', \App\Http\Controllers\Api\Student\mobile\AcademicsController::class);
    Route::get('/timetable', \App\Http\Controllers\Api\Student\mobile\TimetableController::class);

    Route::get('/finance', [\App\Http\Controllers\Api\Student\mobile\FinanceController::class, 'show']);
    Route::post('/finance/invoices/{invoice}/pay', [\App\Http\Controllers\Api\Student\mobile\FinanceController::class, 'pay']);
    Route::get('/finance/mpesa/{stkRequest}/status', [\App\Http\Controllers\Api\Student\mobile\FinanceController::class, 'stkStatus']);

    Route::get('/profile', [\App\Http\Controllers\Api\Student\mobile\ProfileController::class, 'show']);
    Route::put('/profile', [\App\Http\Controllers\Api\Student\mobile\ProfileController::class, 'update']);

    Route::get('/documents', [\App\Http\Controllers\Api\Student\mobile\DocumentsController::class, 'index']);
    Route::post('/documents/requests', [\App\Http\Controllers\Api\Student\mobile\DocumentsController::class, 'storeRequest']);

    Route::get('/notifications', [\App\Http\Controllers\Api\Student\mobile\NotificationsController::class, 'index']);
    Route::post('/notifications/{notification}/read', [\App\Http\Controllers\Api\Student\mobile\NotificationsController::class, 'markRead']);

    Route::get('/clearance', [\App\Http\Controllers\Api\Student\mobile\ClearanceController::class, 'index']);
    Route::post('/clearance/ensure', [\App\Http\Controllers\Api\Student\mobile\ClearanceController::class, 'ensure']);

    Route::get('/evaluations', [\App\Http\Controllers\Api\Student\mobile\EvaluationsController::class, 'index']);
    Route::post('/evaluations', [\App\Http\Controllers\Api\Student\mobile\EvaluationsController::class, 'store']);

    Route::get('/requests/lifecycle', [\App\Http\Controllers\Api\Student\mobile\RequestsController::class, 'lifecycleIndex']);
    Route::post('/requests/lifecycle', [\App\Http\Controllers\Api\Student\mobile\RequestsController::class, 'storeLifecycle']);
    Route::post('/requests/suggestions', [\App\Http\Controllers\Api\Student\mobile\RequestsController::class, 'storeSuggestion']);
    Route::post('/requests/transcript', [\App\Http\Controllers\Api\Student\mobile\RequestsController::class, 'storeTranscript']);
    Route::post('/requests/exam-sitting', [\App\Http\Controllers\Api\Student\mobile\RequestsController::class, 'storeExamSitting']);

    Route::get('/assessments/{assessment}', [\App\Http\Controllers\Api\Student\mobile\AssessmentsController::class, 'show']);
    Route::post('/assessments/{assessment}/submit', [\App\Http\Controllers\Api\Student\mobile\AssessmentsController::class, 'submit']);
});

/*
| Mobile / Sanctum teaching Staff Portal (separate from Employee HR portal).
| MFA intentionally not required (mobile v1).
*/
Route::middleware(['auth:sanctum', 'staff.portal'])->prefix('staff')->group(function () {
    Route::get('/dashboard', \App\Http\Controllers\Api\Staff\mobile\DashboardController::class);
    Route::get('/notifications', \App\Http\Controllers\Api\Staff\mobile\NotificationController::class);
    Route::get('/units', \App\Http\Controllers\Api\Staff\mobile\UnitsController::class);
    Route::get('/timetable', \App\Http\Controllers\Api\Staff\mobile\TimetableController::class);

    Route::get('/attendance', [\App\Http\Controllers\Api\Staff\mobile\AttendanceController::class, 'index']);
    Route::post('/attendance/sync', [\App\Http\Controllers\Api\Staff\mobile\AttendanceController::class, 'sync']);
    Route::post('/attendance', [\App\Http\Controllers\Api\Staff\mobile\AttendanceController::class, 'store']);
    Route::get('/attendance/{session}', [\App\Http\Controllers\Api\Staff\mobile\AttendanceController::class, 'show']);
    Route::post('/attendance/{session}', [\App\Http\Controllers\Api\Staff\mobile\AttendanceController::class, 'save']);
    Route::post('/attendance/{session}/sheet', [\App\Http\Controllers\Api\Staff\mobile\AttendanceController::class, 'uploadSheet']);
    Route::post('/attendance/{session}/class-photo', [\App\Http\Controllers\Api\Staff\mobile\AttendanceController::class, 'uploadClassPhoto']);

    Route::get('/grading', [\App\Http\Controllers\Api\Staff\mobile\GradingController::class, 'index']);
    Route::post('/grading/cat', [\App\Http\Controllers\Api\Staff\mobile\GradingController::class, 'storeCat']);

    Route::get('/lesson-plans', [\App\Http\Controllers\Api\Staff\mobile\LessonPlansController::class, 'index']);
    Route::get('/lesson-plans/context', [\App\Http\Controllers\Api\Staff\mobile\LessonPlansController::class, 'context']);
    Route::post('/lesson-plans', [\App\Http\Controllers\Api\Staff\mobile\LessonPlansController::class, 'store']);
    Route::post('/lesson-plans/{plan}/submit', [\App\Http\Controllers\Api\Staff\mobile\LessonPlansController::class, 'submit']);

    Route::get('/content', [\App\Http\Controllers\Api\Staff\mobile\ContentController::class, 'index']);
    Route::post('/content', [\App\Http\Controllers\Api\Staff\mobile\ContentController::class, 'store']);
});

Route::middleware(['auth:sanctum', 'permission:procurement.read'])->prefix('procurement')->group(function () {
    Route::get('/dashboard', \App\Http\Controllers\Api\Procurement\mobile\DashboardController::class);
    Route::get('/sidebar-notifications', \App\Http\Controllers\Api\Procurement\mobile\SidebarNotificationsController::class);

    Route::get('/requisitions/form-options', [\App\Http\Controllers\Api\Procurement\mobile\RequisitionsController::class, 'formOptions']);
    Route::get('/requisitions/budget-lines', [\App\Http\Controllers\Api\Procurement\mobile\RequisitionsController::class, 'budgetLines']);
    Route::post('/requisitions', [\App\Http\Controllers\Api\Procurement\mobile\RequisitionsController::class, 'store']);
    Route::get('/requisitions', [\App\Http\Controllers\Api\Procurement\mobile\RequisitionsController::class, 'index']);
    Route::get('/requisitions/{requisition}', [\App\Http\Controllers\Api\Procurement\mobile\RequisitionsController::class, 'show']);
    Route::post('/requisitions/{requisition}/approve', [\App\Http\Controllers\Api\Procurement\mobile\RequisitionsController::class, 'approve']);
    Route::post('/requisitions/{requisition}/reject', [\App\Http\Controllers\Api\Procurement\mobile\RequisitionsController::class, 'reject']);
    Route::post('/requisitions/{requisition}/submit', [\App\Http\Controllers\Api\Procurement\mobile\RequisitionsController::class, 'submit']);

    Route::get('/stock-issues/form-options', [\App\Http\Controllers\Api\Procurement\mobile\StockIssuesController::class, 'formOptions']);
    Route::post('/stock-issues', [\App\Http\Controllers\Api\Procurement\mobile\StockIssuesController::class, 'store']);
    Route::get('/stock-issues', [\App\Http\Controllers\Api\Procurement\mobile\StockIssuesController::class, 'index']);
    Route::get('/stock-issues/{stockIssue}', [\App\Http\Controllers\Api\Procurement\mobile\StockIssuesController::class, 'show']);
    Route::post('/stock-issues/{stockIssue}/approve', [\App\Http\Controllers\Api\Procurement\mobile\StockIssuesController::class, 'approve']);

    Route::get('/grns', [\App\Http\Controllers\Api\Procurement\mobile\GrnsController::class, 'index']);
    Route::get('/grns/{grn}', [\App\Http\Controllers\Api\Procurement\mobile\GrnsController::class, 'show']);
    Route::post('/grns/{grn}/complete-inspection', [\App\Http\Controllers\Api\Procurement\mobile\GrnsController::class, 'completeInspection']);

    Route::get('/stock-alerts', [\App\Http\Controllers\Api\Procurement\mobile\StockAlertsController::class, 'index']);
    Route::get('/stock-alerts/{stockAlert}', [\App\Http\Controllers\Api\Procurement\mobile\StockAlertsController::class, 'show']);
    Route::post('/stock-alerts/{stockAlert}/auto-reorder', [\App\Http\Controllers\Api\Procurement\mobile\StockAlertsController::class, 'autoReorder']);

    Route::get('/assets', [\App\Http\Controllers\Api\Procurement\mobile\AssetsController::class, 'index']);
    Route::get('/assets/{asset}', [\App\Http\Controllers\Api\Procurement\mobile\AssetsController::class, 'show']);

    Route::get('/suppliers', [\App\Http\Controllers\Api\Procurement\mobile\SuppliersController::class, 'index']);
    Route::post('/suppliers', [\App\Http\Controllers\Api\Procurement\mobile\SuppliersController::class, 'store']);
    Route::get('/suppliers/{supplier}', [\App\Http\Controllers\Api\Procurement\mobile\SuppliersController::class, 'show']);
    Route::post('/suppliers/{supplier}/verify', [\App\Http\Controllers\Api\Procurement\mobile\SuppliersController::class, 'verify']);

    Route::get('/rfqs', [\App\Http\Controllers\Api\Procurement\mobile\RfqsController::class, 'index']);
    Route::get('/rfqs/{rfq}', [\App\Http\Controllers\Api\Procurement\mobile\RfqsController::class, 'show']);
    Route::post('/rfqs/{rfq}/approve-award', [\App\Http\Controllers\Api\Procurement\mobile\RfqsController::class, 'approveAward']);
    Route::post('/rfqs/{rfq}/reject-award', [\App\Http\Controllers\Api\Procurement\mobile\RfqsController::class, 'rejectAward']);

    Route::get('/inventory-items', [\App\Http\Controllers\Api\Procurement\mobile\InventoryItemsController::class, 'index']);
    Route::get('/inventory-items/{inventoryItem}', [\App\Http\Controllers\Api\Procurement\mobile\InventoryItemsController::class, 'show']);

    Route::get('/asset-disposals/form-options', [\App\Http\Controllers\Api\Procurement\mobile\AssetDisposalsController::class, 'formOptions']);
    Route::post('/asset-disposals', [\App\Http\Controllers\Api\Procurement\mobile\AssetDisposalsController::class, 'store']);
    Route::get('/asset-disposals', [\App\Http\Controllers\Api\Procurement\mobile\AssetDisposalsController::class, 'index']);
    Route::get('/asset-disposals/{assetDisposal}', [\App\Http\Controllers\Api\Procurement\mobile\AssetDisposalsController::class, 'show']);
    Route::post('/asset-disposals/{assetDisposal}/approve', [\App\Http\Controllers\Api\Procurement\mobile\AssetDisposalsController::class, 'approve']);

    Route::get('/asset-audits', [\App\Http\Controllers\Api\Procurement\mobile\AssetAuditsController::class, 'index']);
    Route::get('/asset-audits/{assetAudit}', [\App\Http\Controllers\Api\Procurement\mobile\AssetAuditsController::class, 'show']);
    Route::post('/asset-audits/{assetAudit}/review', [\App\Http\Controllers\Api\Procurement\mobile\AssetAuditsController::class, 'review']);

    Route::get('/payment-verification', [\App\Http\Controllers\Api\Procurement\mobile\PaymentVerificationController::class, 'index']);
});
