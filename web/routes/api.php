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
    Route::post('/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/mfa/challenge', [AuthController::class, 'mfaChallenge']);
        Route::post('/mfa/setup', [AuthController::class, 'mfaSetup']);
        Route::post('/mfa/setup/verify', [AuthController::class, 'mfaSetupVerify']);
        Route::post('/mfa/disable', [AuthController::class, 'mfaDisable']);
        Route::post('/logout', [AuthController::class, 'logout']);

        Route::prefix('mfa')->group(function () {
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
