<?php

use App\Http\Controllers\ApplicationDocumentController;
use App\Http\Controllers\Academics\AcademicClearanceController;
use App\Http\Controllers\Academics\ApplicationReviewController;
use App\Http\Controllers\Academics\AttendanceLedgerController;
use App\Http\Controllers\Academics\CalendarController as AcademicsCalendarController;
use App\Http\Controllers\Academics\CourseEvaluationWindowController;
use App\Http\Controllers\Academics\DashboardController as AcademicsDashboardController;
use App\Http\Controllers\Academics\DepartmentController as AcademicsDepartmentController;
use App\Http\Controllers\Academics\DocumentRequestController;
use App\Http\Controllers\Academics\ExaminationPaperController;
use App\Http\Controllers\Academics\LessonPlanController;
use App\Http\Controllers\Academics\LifecycleRequestController;
use App\Http\Controllers\Academics\PerformanceTerminalController;
use App\Http\Controllers\Academics\ProgramCurriculumController;
use App\Http\Controllers\Academics\SidebarNotificationController as AcademicsSidebarNotificationController;
use App\Http\Controllers\Academics\SpecialExamRequestController;
use App\Http\Controllers\Academics\StudentProfileChangeController;
use App\Http\Controllers\Academics\SuggestionBoxController;
use App\Http\Controllers\Academics\SupplementaryRequestController;
use App\Http\Controllers\Academics\TranscriptRequestController;
use App\Http\Controllers\Academics\UnitController as AcademicsUnitController;
use Illuminate\Support\Facades\Route;

return function (bool $named = true): void {
    $routeName = static function (string $name) use ($named): ?string {
        return $named ? $name : null;
    };

    $dashboard = Route::get('/', AcademicsDashboardController::class)
        ->middleware('permission:academics.read');

    if ($named) {
        $dashboard->name('departments.academics.dashboard');
    }

    Route::middleware('permission:academics.read')->group(function () use ($named, $routeName) {
        $register = static function (string $method, string $uri, array|string $action, string $name, array $middleware = []) use ($named, $routeName) {
            $route = Route::{$method}($uri, $action);

            foreach ($middleware as $item) {
                $route->middleware($item);
            }

            if ($named) {
                $route->name($name);
            }
        };

        $register('get', '/sidebar-notifications', AcademicsSidebarNotificationController::class, 'departments.academics.sidebar-notifications');
        $register('get', '/applications', [ApplicationReviewController::class, 'index'], 'departments.academics.applications.index');
        $register('get', '/applications/{id}', [ApplicationReviewController::class, 'show'], 'departments.academics.applications.show');
        $register('get', '/applications/{applicationId}/documents/{documentId}', [ApplicationDocumentController::class, 'show'], 'departments.academics.applications.documents.show');
        $register('get', '/applications/{applicationId}/documents/{documentId}/download', [ApplicationDocumentController::class, 'download'], 'departments.academics.applications.documents.download');
        $register('get', '/departments', [AcademicsDepartmentController::class, 'index'], 'departments.academics.departments.index');
        $register('get', '/units', [AcademicsUnitController::class, 'index'], 'departments.academics.units.index');
        $register('get', '/programs', [ProgramCurriculumController::class, 'index'], 'departments.academics.programs.index');
        $register('get', '/programs/{program}/curriculum', [ProgramCurriculumController::class, 'show'], 'departments.academics.programs.curriculum');
        $register('get', '/programs/{program}/timetables/{timetable}/print', [ProgramCurriculumController::class, 'printTimetable'], 'departments.academics.programs.timetable.print');
        $register('get', '/programs/{program}/timetables/{timetable}/pdf', [ProgramCurriculumController::class, 'downloadTimetablePdf'], 'departments.academics.programs.timetable.pdf');
        $register('get', '/attendance-ledger', [AttendanceLedgerController::class, 'index'], 'departments.academics.attendance-ledger.index');
        $register('get', '/clearance', [AcademicClearanceController::class, 'index'], 'departments.academics.clearance.index');
        $register('get', '/suggestions', [SuggestionBoxController::class, 'index'], 'departments.academics.suggestions.index');
        $register('get', '/suggestions/{suggestion}', [SuggestionBoxController::class, 'show'], 'departments.academics.suggestions.show');
        $register('get', '/profile-changes', [StudentProfileChangeController::class, 'index'], 'departments.academics.profile-changes.index');
        $register('get', '/profile-changes/{profileChange}', [StudentProfileChangeController::class, 'show'], 'departments.academics.profile-changes.show');
        $register('get', '/transcript-requests', [TranscriptRequestController::class, 'index'], 'departments.academics.transcript-requests.index');
        $register('get', '/transcript-requests/{transcriptRequest}', [TranscriptRequestController::class, 'show'], 'departments.academics.transcript-requests.show');
        $register('get', '/lifecycle-requests', [LifecycleRequestController::class, 'index'], 'departments.academics.lifecycle-requests.index');
        $register('get', '/lifecycle-requests/{lifecycleRequest}', [LifecycleRequestController::class, 'show'], 'departments.academics.lifecycle-requests.show');
        $register('get', '/lifecycle-requests/{lifecycleRequest}/attachments/{index}', [LifecycleRequestController::class, 'downloadAttachment'], 'departments.academics.lifecycle-requests.attachment');
        $register('get', '/evaluation-windows', [CourseEvaluationWindowController::class, 'index'], 'departments.academics.evaluation-windows.index');
        $register('get', '/document-requests', [DocumentRequestController::class, 'index'], 'departments.academics.document-requests.index');
        $register('get', '/document-requests/{documentRequest}', [DocumentRequestController::class, 'show'], 'departments.academics.document-requests.show');
        $register('get', '/special-exam-requests', [SpecialExamRequestController::class, 'index'], 'departments.academics.special-exam-requests.index');
        $register('get', '/special-exam-requests/{specialExamRequest}', [SpecialExamRequestController::class, 'show'], 'departments.academics.special-exam-requests.show');
        $register('get', '/supplementary-requests', [SupplementaryRequestController::class, 'index'], 'departments.academics.supplementary-requests.index');
        $register('get', '/supplementary-requests/{supplementaryExamRequest}', [SupplementaryRequestController::class, 'show'], 'departments.academics.supplementary-requests.show');
        $register('get', '/examination-papers/{examinationPaper}/download/{kind}', [ExaminationPaperController::class, 'download'], 'departments.academics.examination-papers.download');
        $register('get', '/lesson-plans', [LessonPlanController::class, 'index'], 'departments.academics.lesson-plans.index');
        $register('get', '/lesson-plans/audit', [LessonPlanController::class, 'audit'], 'departments.academics.lesson-plans.audit');
        $register('get', '/lesson-plans/{plan}', [LessonPlanController::class, 'show'], 'departments.academics.lesson-plans.show');
        $register('get', '/performance', [PerformanceTerminalController::class, 'index'], 'departments.academics.performance.index');
    });

    Route::middleware('permission:academics.write')->group(function () use ($named) {
        $register = static function (string $method, string $uri, array|string $action, string $name, array $middleware = []) use ($named) {
            $route = Route::{$method}($uri, $action);

            foreach ($middleware as $item) {
                $route->middleware($item);
            }

            if ($named) {
                $route->name($name);
            }
        };

        $register('post', '/applications/{id}/approve', [ApplicationReviewController::class, 'approve'], 'departments.academics.applications.approve', ['permission:academics.approve']);
        $register('post', '/applications/{id}/reject', [ApplicationReviewController::class, 'reject'], 'departments.academics.applications.reject', ['permission:academics.approve']);
        $register('post', '/programs/{program}/allocations', [ProgramCurriculumController::class, 'storeAllocation'], 'departments.academics.programs.allocations.store');
        $register('delete', '/programs/{program}/allocations/{allocation}', [ProgramCurriculumController::class, 'destroyAllocation'], 'departments.academics.programs.allocations.destroy');
        $register('post', '/attendance-ledger/{session}/verify-hod', [AttendanceLedgerController::class, 'verifyHod'], 'departments.academics.attendance-ledger.verify-hod');
        $register('post', '/clearance/{student}/approve', [AcademicClearanceController::class, 'approve'], 'departments.academics.clearance.approve');
        $register('post', '/clearance/{student}/reject', [AcademicClearanceController::class, 'reject'], 'departments.academics.clearance.reject');
        $register('put', '/suggestions/{suggestion}', [SuggestionBoxController::class, 'update'], 'departments.academics.suggestions.update');
        $register('post', '/profile-changes/{profileChange}/approve', [StudentProfileChangeController::class, 'approve'], 'departments.academics.profile-changes.approve');
        $register('post', '/profile-changes/{profileChange}/reject', [StudentProfileChangeController::class, 'reject'], 'departments.academics.profile-changes.reject');
        $register('post', '/transcript-requests/{transcriptRequest}/issue', [TranscriptRequestController::class, 'issue'], 'departments.academics.transcript-requests.issue');
        $register('post', '/transcript-requests/{transcriptRequest}/reject', [TranscriptRequestController::class, 'reject'], 'departments.academics.transcript-requests.reject');
        $register('post', '/lifecycle-requests/{lifecycleRequest}/approve', [LifecycleRequestController::class, 'approve'], 'departments.academics.lifecycle-requests.approve');
        $register('post', '/lifecycle-requests/{lifecycleRequest}/reject', [LifecycleRequestController::class, 'reject'], 'departments.academics.lifecycle-requests.reject');
        $register('post', '/lifecycle-requests/{lifecycleRequest}/hold', [LifecycleRequestController::class, 'hold'], 'departments.academics.lifecycle-requests.hold');
        $register('post', '/evaluation-windows', [CourseEvaluationWindowController::class, 'store'], 'departments.academics.evaluation-windows.store');
        $register('put', '/evaluation-windows/{evaluationWindow}', [CourseEvaluationWindowController::class, 'update'], 'departments.academics.evaluation-windows.update');
        $register('post', '/document-requests/{documentRequest}/issue', [DocumentRequestController::class, 'issue'], 'departments.academics.document-requests.issue');
        $register('post', '/document-requests/{documentRequest}/reject', [DocumentRequestController::class, 'reject'], 'departments.academics.document-requests.reject');
        $register('post', '/special-exam-requests/{specialExamRequest}/approve', [SpecialExamRequestController::class, 'approve'], 'departments.academics.special-exam-requests.approve');
        $register('post', '/special-exam-requests/{specialExamRequest}/reject', [SpecialExamRequestController::class, 'reject'], 'departments.academics.special-exam-requests.reject');
        $register('post', '/special-exam-requests/{specialExamRequest}/hold', [SpecialExamRequestController::class, 'hold'], 'departments.academics.special-exam-requests.hold');
        $register('post', '/supplementary-requests/{supplementaryExamRequest}/approve', [SupplementaryRequestController::class, 'approve'], 'departments.academics.supplementary-requests.approve');
        $register('post', '/supplementary-requests/{supplementaryExamRequest}/reject', [SupplementaryRequestController::class, 'reject'], 'departments.academics.supplementary-requests.reject');
        $register('post', '/supplementary-requests/{supplementaryExamRequest}/hold', [SupplementaryRequestController::class, 'hold'], 'departments.academics.supplementary-requests.hold');
        $register('post', '/examination-papers/{examinationPaper}/moderate', [ExaminationPaperController::class, 'moderate'], 'departments.academics.examination-papers.moderate');
        $register('post', '/examination-papers/{examinationPaper}/approve', [ExaminationPaperController::class, 'approve'], 'departments.academics.examination-papers.approve');
        $register('post', '/attendance-ledger/{session}/verify-registrar', [AttendanceLedgerController::class, 'verifyRegistrar'], 'departments.academics.attendance-ledger.verify-registrar');
        $register('put', '/lesson-plans/{plan}', [LessonPlanController::class, 'update'], 'departments.academics.lesson-plans.update');
        $register('post', '/lesson-plans/{plan}/approve', [LessonPlanController::class, 'approve'], 'departments.academics.lesson-plans.approve');
        $register('post', '/lesson-plans/{plan}/reject', [LessonPlanController::class, 'reject'], 'departments.academics.lesson-plans.reject');
        $register('post', '/lesson-plans/{plan}/request-modification', [LessonPlanController::class, 'requestModification'], 'departments.academics.lesson-plans.request-modification');
        $register('put', '/learning-departments/{learningDepartment}/profile', [AcademicsDepartmentController::class, 'updateProfile'], 'departments.academics.departments.update-profile');
        $register('post', '/units', [AcademicsUnitController::class, 'store'], 'departments.academics.units.store');
        $register('put', '/units/{unit}', [AcademicsUnitController::class, 'update'], 'departments.academics.units.update');
        $register('post', '/units/{unit}/submit', [AcademicsUnitController::class, 'submit'], 'departments.academics.units.submit');
        $register('put', '/programs/{program}/format', [ProgramCurriculumController::class, 'updateFormat'], 'departments.academics.programs.update-format');
        $register('post', '/programs/{program}/units', [ProgramCurriculumController::class, 'syncUnits'], 'departments.academics.programs.sync-units');
        $register('post', '/programs/{program}/intakes', [ProgramCurriculumController::class, 'createVersion'], 'departments.academics.programs.intakes.store');
        $register('post', '/programs/{program}/intakes/{version}/units', [ProgramCurriculumController::class, 'syncIntakeUnits'], 'departments.academics.programs.intakes.sync-units');
        $register('post', '/programs/{program}/intakes/{version}/periods', [ProgramCurriculumController::class, 'syncIntakePeriods'], 'departments.academics.programs.intakes.sync-periods');
        $register('put', '/programs/{program}/timetable/template', [ProgramCurriculumController::class, 'syncTimetableTemplate'], 'departments.academics.programs.timetable.sync-template');
        $register('put', '/programs/{program}/timetable/slots', [ProgramCurriculumController::class, 'syncTimetableKindSlots'], 'departments.academics.programs.timetable.sync-kind-slots');
        $register('post', '/programs/{program}/intakes/{version}/timetable/generate', [ProgramCurriculumController::class, 'generateTimetable'], 'departments.academics.programs.timetable.generate');
        $register('post', '/programs/{program}/timetables/{timetable}/sessions', [ProgramCurriculumController::class, 'addTimetableSession'], 'departments.academics.programs.timetable.add-session');
        $register('patch', '/programs/{program}/timetables/{timetable}/sessions/{session}/move', [ProgramCurriculumController::class, 'moveTimetableSession'], 'departments.academics.programs.timetable.move-session');
        $register('post', '/programs/{program}/timetables/{timetable}/publish', [ProgramCurriculumController::class, 'publishTimetable'], 'departments.academics.programs.timetable.publish');
        $register('put', '/programs/{program}/exam-schedules/{schedule}', [ProgramCurriculumController::class, 'updateExamSchedule'], 'departments.academics.programs.exam-schedules.update');
        $register('put', '/programs/{program}/units/{unit}/assessment-weights', [ProgramCurriculumController::class, 'updateUnitAssessmentWeights'], 'departments.academics.programs.units.assessment-weights.update');
        $register('post', '/programs/{program}/intakes/{version}/semesters/{semester}/units', [ProgramCurriculumController::class, 'addIntakeUnit'], 'departments.academics.programs.intakes.add-unit');
        $register('post', '/programs/{program}/versions', [ProgramCurriculumController::class, 'createVersion'], 'departments.academics.programs.versions.create');
        $register('post', '/versions/{version}/submit', [ProgramCurriculumController::class, 'submitVersion'], 'departments.academics.versions.submit');
        $register('post', '/versions/{version}/reopen', [ProgramCurriculumController::class, 'reopenVersion'], 'departments.academics.versions.reopen');
    });

    $approveUnit = Route::post('/units/{unit}/approve', [AcademicsUnitController::class, 'approve'])
        ->middleware('permission:academics.approve');

    if ($named) {
        $approveUnit->name('departments.academics.units.approve');
    }

    $approveRegistry = Route::post('/versions/{version}/approve-registry', [ProgramCurriculumController::class, 'approveVersionRegistry'])
        ->middleware('permission:academics.approve');

    if ($named) {
        $approveRegistry->name('departments.academics.versions.approve-registry');
    }

    $approveCeo = Route::post('/versions/{version}/approve-ceo', [ProgramCurriculumController::class, 'approveVersionCeo'])
        ->middleware('permission:academics.approve');

    if ($named) {
        $approveCeo->name('departments.academics.versions.approve-ceo');
    }

    Route::middleware('permission:academics.calendar')->group(function () use ($named) {
        $calendar = Route::get('/calendar', [AcademicsCalendarController::class, 'index']);

        if ($named) {
            $calendar->name('departments.academics.calendar.index');
        }

        $storeYear = Route::post('/calendar/years', [AcademicsCalendarController::class, 'storeYear']);

        if ($named) {
            $storeYear->name('departments.academics.calendar.store-year');
        }

        $updateSemester = Route::put('/calendar/semesters/{semester}', [AcademicsCalendarController::class, 'updateSemester']);

        if ($named) {
            $updateSemester->name('departments.academics.calendar.update-semester');
        }
    });
};
