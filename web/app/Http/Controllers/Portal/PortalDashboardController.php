<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\CourseEvaluation;
use App\Models\CourseEvaluationWindow;
use App\Models\SpecialExamRequest;
use App\Models\StudentDocumentRequest;
use App\Models\StudentLifecycleRequest;
use App\Models\StudentNotification;
use App\Models\StudentProfileChangeRequest;
use App\Models\StudentTranscriptRequest;
use App\Models\SupplementaryExamRequest;
use App\Services\StudentClearanceService;
use App\Services\StudentPortalDashboardService;
use App\Services\StudentPortalNavigationService;
use App\Services\StudentPortalService;
use App\Services\StudentRecordService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class PortalDashboardController extends Controller
{
    public function __construct(
        protected StudentPortalService $portalService,
        protected StudentRecordService $studentRecords,
        protected StudentPortalNavigationService $navigation,
        protected StudentPortalDashboardService $dashboard,
        protected StudentClearanceService $clearance,
    ) {}

    public function __invoke(Request $request): View
    {
        $student = $this->portalService->studentForUser($request->user());

        abort_if(! $student, 404);

        $biodata = $this->studentRecords->biodata360($student);
        $section = $this->navigation->resolveSection($request);
        $tab = $this->navigation->resolveTab($request, $section);
        $portalData = $this->dashboard->forStudent($student, $biodata);

        $profileChangeRequests = collect();
        $lifecycleRequests = collect();
        $transcriptRequests = collect();
        $documentRequests = collect();
        $evaluationWindows = collect();
        $myEvaluations = collect();
        $notifications = collect();
        $clearanceItems = collect();
        $specialExamRequests = collect();
        $supplementaryExamRequests = collect();

        if (Schema::hasTable('student_profile_change_requests') && $section === 'profile') {
            $profileChangeRequests = StudentProfileChangeRequest::query()
                ->where('student_id', $student->id)
                ->orderByDesc('created_at')
                ->limit(20)
                ->get();
        }

        if (Schema::hasTable('student_lifecycle_requests') && $section === 'requests') {
            $lifecycleRequests = StudentLifecycleRequest::query()
                ->where('student_id', $student->id)
                ->orderByDesc('created_at')
                ->limit(30)
                ->get();
        }

        if ($section === 'exam-requests') {
            if (Schema::hasTable('special_exam_requests')) {
                $specialExamRequests = SpecialExamRequest::query()
                    ->with(['unit', 'semester'])
                    ->where('student_id', $student->id)
                    ->orderByDesc('created_at')
                    ->limit(30)
                    ->get();
            }
            if (Schema::hasTable('supplementary_requests')) {
                $supplementaryExamRequests = SupplementaryExamRequest::query()
                    ->with(['unit', 'semester'])
                    ->where('student_id', $student->id)
                    ->orderByDesc('created_at')
                    ->limit(30)
                    ->get();
            }
        }

        if (Schema::hasTable('student_transcript_requests') && $section === 'academics' && $tab === 'exams') {
            $transcriptRequests = StudentTranscriptRequest::query()
                ->where('student_id', $student->id)
                ->orderByDesc('created_at')
                ->limit(10)
                ->get();
        }

        if (Schema::hasTable('student_document_requests') && $section === 'documents') {
            $documentRequests = StudentDocumentRequest::query()
                ->where('student_id', $student->id)
                ->orderByDesc('created_at')
                ->limit(20)
                ->get();
        }

        if (Schema::hasTable('course_evaluation_windows') && $section === 'evaluations') {
            $evaluationWindows = CourseEvaluationWindow::query()
                ->where('is_active', true)
                ->orderByDesc('opens_at')
                ->get()
                ->filter(fn (CourseEvaluationWindow $window) => $window->isOpen())
                ->values();

            $myEvaluations = CourseEvaluation::query()
                ->where('student_id', $student->id)
                ->orderByDesc('submitted_at')
                ->limit(30)
                ->get();
        }

        if (Schema::hasTable('student_notifications') && in_array($section, ['notifications', 'overview'], true)) {
            $notifications = StudentNotification::query()
                ->where('student_id', $student->id)
                ->orderByDesc('created_at')
                ->limit(50)
                ->get();
        }

        if (Schema::hasTable('student_clearance_items') && $section === 'clearance') {
            $clearanceItems = $this->clearance->ensureDefaults($student);
        }

        return view('portal.dashboard', [
            'student' => $student,
            'biodata' => $biodata,
            'portalData' => $portalData,
            'section' => $section,
            'tab' => $tab,
            'academicsTab' => $section === 'academics' ? $tab : null,
            'timetableTab' => $section === 'timetable' ? $tab : null,
            'sections' => $this->navigation->sections(),
            'sidebarNavigation' => $this->navigation->sidebarNavigation($student),
            'modules' => $this->navigation->modules(),
            'portalTitle' => $this->navigation->portalTitle($section, $tab),
            'profileChangeRequests' => $profileChangeRequests,
            'lifecycleRequests' => $lifecycleRequests,
            'transcriptRequests' => $transcriptRequests,
            'documentRequests' => $documentRequests,
            'evaluationWindows' => $evaluationWindows,
            'myEvaluations' => $myEvaluations,
            'notifications' => $notifications,
            'clearanceItems' => $clearanceItems,
            'specialExamRequests' => $specialExamRequests,
            'supplementaryExamRequests' => $supplementaryExamRequests,
            'lifecycleTypes' => StudentLifecycleRequest::TYPES,
            'documentTypes' => StudentDocumentRequest::TYPES,
        ]);
    }
}
