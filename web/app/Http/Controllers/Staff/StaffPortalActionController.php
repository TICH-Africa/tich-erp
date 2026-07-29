<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\AttendanceSession;
use App\Models\LessonPlan;
use App\Models\ObjectiveAssessment;
use App\Models\UnitAllocation;
use App\Services\AttendanceSessionGenerationService;
use App\Services\ContinuousAssessmentService;
use App\Services\LessonPlanApprovalService;
use App\Services\ObjectiveAutoGradingService;
use App\Services\StaffPortalService;
use App\Services\StaffTeachingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class StaffPortalActionController extends Controller
{
    public function __construct(
        protected StaffPortalService $portalService,
        protected StaffTeachingService $teaching,
        protected AttendanceSessionGenerationService $attendanceGeneration,
        protected ContinuousAssessmentService $assessments,
        protected LessonPlanApprovalService $lessonPlanApprovals,
        protected ObjectiveAutoGradingService $objectiveGrading,
    ) {}

    public function storeLessonPlan(Request $request): RedirectResponse
    {
        $staff = $this->portalService->staffForUser($request->user());
        $allocation = UnitAllocation::query()->findOrFail($request->integer('allocation_id'));

        $validated = $request->validate([
            'allocation_id' => ['required', 'integer'],
            'lesson_objectives' => ['required', 'string'],
            'topics_covered' => ['nullable', 'string'],
            'competencies_targeted' => ['nullable', 'string'],
            'planned_date' => ['required', 'date'],
            'week_number' => ['nullable', 'integer', 'min:1'],
            'contact_hours' => ['required', 'integer', 'min:1'],
            'teaching_methods' => ['nullable', 'string', 'max:500'],
            'resources_required' => ['nullable', 'string', 'max:500'],
            'submit' => ['nullable', 'boolean'],
        ]);

        $plan = $this->teaching->createLessonPlan($staff, $allocation, $validated);

        if ($request->boolean('submit')) {
            $this->teaching->submitLessonPlan($plan, $staff);
        }

        return redirect()->route('staff.dashboard', ['section' => 'lesson-plans'])
            ->with('status', 'Lesson plan saved.');
    }

    public function submitLessonPlan(Request $request, LessonPlan $plan): RedirectResponse
    {
        $staff = $this->portalService->staffForUser($request->user());
        $this->teaching->submitLessonPlan($plan, $staff);

        return back()->with('status', 'Lesson plan submitted for HOD approval.');
    }

    public function updateLessonPlan(Request $request, LessonPlan $plan): RedirectResponse
    {
        $staff = $this->portalService->staffForUser($request->user());
        abort_unless((int) $plan->prepared_by === (int) $staff->id, 403);

        $validated = $request->validate([
            'lesson_objectives' => ['required', 'string'],
            'topics_covered' => ['nullable', 'string'],
            'competencies_targeted' => ['nullable', 'string'],
            'planned_date' => ['required', 'date'],
            'week_number' => ['nullable', 'integer', 'min:1'],
            'contact_hours' => ['required', 'integer', 'min:1'],
            'teaching_methods' => ['nullable', 'string', 'max:500'],
            'resources_required' => ['nullable', 'string', 'max:500'],
        ]);

        $this->teaching->updateLessonPlan($plan, $staff, $validated);

        return redirect()->route('staff.dashboard', ['section' => 'lesson-plans', 'edit_plan' => $plan->id])
            ->with('status', 'Lesson plan updated.');
    }

    public function storeAttendanceSession(Request $request): RedirectResponse
    {
        $staff = $this->portalService->staffForUser($request->user());
        $allocation = UnitAllocation::query()->findOrFail($request->integer('allocation_id'));

        $validated = $request->validate([
            'allocation_id' => ['required', 'integer'],
            'session_date' => ['required', 'date'],
            'start_time' => ['nullable'],
            'end_time' => ['nullable'],
            'venue' => ['nullable', 'string', 'max:200'],
        ]);

        if (! $this->lessonPlanApprovals->hasApprovedPlanForSession($allocation, $validated['session_date'])) {
            return redirect()->route('staff.dashboard', ['section' => 'lesson-plans'])
                ->withErrors(['lesson_plan' => 'An HOD-approved lesson plan matching this unit and date is required before you can create a class session.']);
        }

        $session = $this->teaching->createAttendanceSession($staff, $allocation, $validated);

        return redirect()->route('staff.dashboard', [
            'section' => 'attendance',
            'attendance_session' => $session->id,
        ])->with('status', 'Attendance session created.');
    }

    public function saveAttendance(Request $request, AttendanceSession $session): RedirectResponse
    {
        $staff = $this->portalService->staffForUser($request->user());

        $present = $request->input('present', []);
        $this->teaching->saveAttendance($session, $staff, is_array($present) ? $present : []);

        if ($request->boolean('lock')) {
            if (! $session->signed_sheet_image_path) {
                return redirect()->route('staff.dashboard', [
                    'section' => 'attendance',
                    'attendance_session' => $session->id,
                ])->withErrors(['signed_sheet' => 'Upload a photo of the signed attendance sheet before submitting.']);
            }

            $this->teaching->lockAttendanceSession($session, $staff);
        }

        return redirect()->route('staff.dashboard', [
            'section' => 'attendance',
            'attendance_session' => $session->id,
        ])->with('status', $request->boolean('lock') ? 'Attendance submitted for HOD/Registrar verification.' : 'Roster saved.');
    }

    public function submitForRosterVerification(Request $request, AttendanceSession $session): RedirectResponse
    {
        $staff = $this->portalService->staffForUser($request->user());
        abort_unless((int) $session->recorded_by === (int) $staff->id, 403);

        app(AttendanceVerificationService::class)->verifyRoster($session, $staff);

        return back()->with('status', 'Roster submitted for verification.');
    }

    public function uploadAttendanceSheet(Request $request, AttendanceSession $session): RedirectResponse
    {
        $staff = $this->portalService->staffForUser($request->user());

        $request->validate([
            'signed_sheet' => ['required', 'file', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
        ]);

        $this->teaching->uploadSignedSheet($session, $staff, $request->file('signed_sheet'));

        return redirect()->route('staff.dashboard', [
            'section' => 'attendance',
            'attendance_session' => $session->id,
        ])->with('status', 'Signed attendance sheet uploaded.');
    }

    public function uploadClassPhoto(Request $request, AttendanceSession $session): RedirectResponse
    {
        $staff = $this->portalService->staffForUser($request->user());

        $request->validate([
            'class_photo' => ['required', 'file', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
        ]);

        $this->teaching->uploadClassPhoto($session, $staff, $request->file('class_photo'));

        return redirect()->route('staff.dashboard', [
            'section' => 'attendance',
            'attendance_session' => $session->id,
        ])->with('status', 'Class photo uploaded.');
    }

    public function syncAttendanceFromTimetable(Request $request): RedirectResponse
    {
        $staff = $this->portalService->staffForUser($request->user());
        $result = $this->attendanceGeneration->syncForStaff($staff);

        $message = $result['created'] > 0
            ? "{$result['created']} attendance session(s) created from your timetable."
            : 'Attendance sessions are already up to date with your timetable.';

        return redirect()->route('staff.dashboard', ['section' => 'attendance'])
            ->with('status', $message);
    }

    public function storeCatScore(Request $request): RedirectResponse
    {
        $staff = $this->portalService->staffForUser($request->user());
        $allocation = UnitAllocation::query()->findOrFail($request->integer('allocation_id'));

        $validated = $request->validate([
            'allocation_id' => ['required', 'integer'],
            'student_id' => ['required', 'integer'],
            'assessment_name' => ['required', 'string', 'max:200'],
            'assessment_type' => ['nullable', 'string', 'max:50'],
            'max_score' => ['required', 'numeric', 'min:0.01'],
            'score_obtained' => ['required', 'numeric', 'min:0'],
            'weight_in_final' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        $this->teaching->recordCatScore($staff, $allocation, $validated);

        return redirect()->route('staff.dashboard', [
            'section' => 'grading',
            'allocation' => $allocation->id,
        ])->with('status', 'Score recorded.');
    }

    public function saveGradingGrid(Request $request): RedirectResponse
    {
        $staff = $this->portalService->staffForUser($request->user());
        $allocation = UnitAllocation::query()->findOrFail($request->integer('allocation_id'));

        $validated = $request->validate([
            'allocation_id' => ['required', 'integer'],
            'columns' => ['required', 'array'],
            'columns.*.key' => ['required', 'string'],
            'columns.*.name' => ['required', 'string'],
            'columns.*.type' => ['required', 'string'],
            'columns.*.max' => ['required', 'numeric', 'min:0.01'],
            'scores' => ['nullable', 'array'],
        ]);

        $this->assessments->saveGrid(
            $allocation,
            $staff,
            $validated['columns'],
            $validated['scores'] ?? [],
        );

        return redirect()->route('staff.dashboard', [
            'section' => 'grading',
            'allocation' => $allocation->id,
        ])->with('status', 'Competency spreadsheet saved. Cumulative scores updated.');
    }

    public function storeObjectiveAssessment(Request $request): RedirectResponse
    {
        $staff = $this->portalService->staffForUser($request->user());
        $allocation = UnitAllocation::query()->findOrFail($request->integer('allocation_id'));

        $validated = $request->validate([
            'allocation_id' => ['required', 'integer'],
            'name' => ['required', 'string', 'max:200'],
            'assessment_type' => ['required', 'string', 'in:mcq,true_false,matching'],
            'max_score' => ['required', 'numeric', 'min:1'],
            'questions' => ['required', 'array'],
            'questions.*.question_text' => ['nullable', 'string'],
            'questions.*.question_type' => ['nullable', 'string'],
            'questions.*.options' => ['nullable', 'string'],
            'questions.*.correct_answer' => ['nullable', 'string'],
            'questions.*.points' => ['nullable', 'numeric', 'min:0.01'],
        ]);

        $questions = array_values(array_filter(
            $validated['questions'],
            fn ($row) => ! empty(trim((string) ($row['question_text'] ?? '')))
        ));

        $assessment = $this->objectiveGrading->createAssessment($staff, $allocation, $validated, $questions);

        return redirect()->route('staff.dashboard', [
            'section' => 'grading',
            'allocation' => $allocation->id,
            'objective_assessment' => $assessment->id,
        ])->with('status', 'Objective assessment created. Enter student responses and run auto-grade.');
    }

    public function saveObjectiveResponses(Request $request): RedirectResponse
    {
        $staff = $this->portalService->staffForUser($request->user());
        $allocation = UnitAllocation::query()->findOrFail($request->integer('allocation_id'));
        $assessment = ObjectiveAssessment::query()->findOrFail($request->integer('objective_assessment_id'));

        $validated = $request->validate([
            'allocation_id' => ['required', 'integer'],
            'objective_assessment_id' => ['required', 'integer'],
            'responses' => ['nullable', 'array'],
        ]);

        $this->objectiveGrading->saveResponses($assessment, $staff, $validated['responses'] ?? []);

        return redirect()->route('staff.dashboard', [
            'section' => 'grading',
            'allocation' => $allocation->id,
            'objective_assessment' => $assessment->id,
        ])->with('status', 'Student responses saved.');
    }

    public function runObjectiveAutoGrade(Request $request): RedirectResponse
    {
        $staff = $this->portalService->staffForUser($request->user());
        $allocation = UnitAllocation::query()->findOrFail($request->integer('allocation_id'));
        $assessment = ObjectiveAssessment::query()->findOrFail($request->integer('objective_assessment_id'));

        $count = $this->objectiveGrading->runAutoGrade($assessment, $staff, $allocation);

        return redirect()->route('staff.dashboard', [
            'section' => 'grading',
            'allocation' => $allocation->id,
            'objective_assessment' => $assessment->id,
        ])->with('status', "Auto-graded {$count} submission(s). Scores synced to cumulative performance sheet.");
    }

    public function storeContent(Request $request): RedirectResponse
    {
        $staff = $this->portalService->staffForUser($request->user());

        $validated = $request->validate([
            'unit_id' => ['required', 'integer'],
            'title' => ['required', 'string', 'max:300'],
            'caption' => ['nullable', 'string', 'max:500'],
            'file' => ['required', 'file', 'max:20480'],
        ]);

        $path = $request->file('file')->store('learning-content', 'public');

        $this->teaching->storeLearningContent($staff, (int) $validated['unit_id'], [
            'title' => $validated['title'],
            'caption' => $validated['caption'] ?? null,
            'file_type' => $request->file('file')->getClientOriginalExtension(),
        ], $path);

        return redirect()->route('staff.dashboard', ['section' => 'content'])
            ->with('status', 'Learning material uploaded.');
    }
}
