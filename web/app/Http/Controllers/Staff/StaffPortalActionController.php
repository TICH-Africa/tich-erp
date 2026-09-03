<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\AttendanceSession;
use App\Models\LessonPlan;
use App\Models\ObjectiveAssessment;
use App\Models\UnitAllocation;
use App\Models\UnitContent;
use App\Services\AttendanceSessionGenerationService;
use App\Services\ContinuousAssessmentService;
use App\Services\LessonPlanApprovalService;
use App\Services\LessonPlanContextService;
use App\Services\LessonPlanDocumentService;
use App\Services\ObjectiveAutoGradingService;
use App\Services\StaffExamMarksService;
use App\Services\StaffPortalService;
use App\Services\StaffTeachingService;
use App\Services\StoredFileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class StaffPortalActionController extends Controller
{
    public function __construct(
        protected StaffPortalService $portalService,
        protected StaffTeachingService $teaching,
        protected AttendanceSessionGenerationService $attendanceGeneration,
        protected StaffExamMarksService $examMarks,
        protected ContinuousAssessmentService $assessments,
        protected LessonPlanApprovalService $lessonPlanApprovals,
        protected LessonPlanDocumentService $lessonPlanDocuments,
        protected LessonPlanContextService $lessonPlanContext,
        protected ObjectiveAutoGradingService $objectiveGrading,
        protected StoredFileService $files,
    ) {}

    public function lessonPlanContext(Request $request): JsonResponse
    {
        $staff = $this->portalService->staffForUser($request->user());
        abort_unless($staff, 403);

        $allocation = UnitAllocation::query()
            ->where('staff_id', $staff->id)
            ->findOrFail($request->integer('allocation_id'));

        $defaults = $this->lessonPlanContext->defaultsForAllocation(
            $staff,
            $allocation,
            $request->string('planned_date')->toString() ?: null,
        );

        return response()->json(['defaults' => $defaults]);
    }

    public function storeLessonPlan(Request $request): RedirectResponse
    {
        $staff = $this->portalService->staffForUser($request->user());
        $allocation = UnitAllocation::query()->findOrFail($request->integer('allocation_id'));
        $sourceType = $request->input('source_type') === 'upload' ? 'upload' : 'form';

        $validated = $request->validate(array_merge(
            $this->lessonPlanFieldRules($sourceType, false),
            $sourceType === 'upload'
                ? ['document' => ['required', 'file', 'max:'.config('tich-lesson-plans.upload.max_kb', 10240), 'mimes:pdf,doc,docx']]
                : [],
        ));

        $planData = $this->lessonPlanDocuments->mapValidatedPlanData($validated, $sourceType);

        if ($sourceType === 'form') {
            abort_if(
                ($planData['form_payload']['session_rows'] ?? []) === [],
                422,
                'Add at least one row to the lesson session plan table.'
            );
        }

        if ($sourceType === 'upload') {
            $upload = $this->lessonPlanDocuments->storeUpload($staff, $request->file('document'));
            $planData = array_merge($planData, $upload);
        }

        $plan = $this->teaching->createLessonPlan($staff, $allocation, $planData);

        if ($request->boolean('submit')) {
            try {
                $this->teaching->submitLessonPlan($plan, $staff);
            } catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) {
                return redirect()->route('staff.dashboard', ['section' => 'lesson-plans', 'edit_plan' => $plan->id])
                    ->withErrors(['lesson_plan' => $exception->getMessage()]);
            }
        }

        return redirect()->route('staff.dashboard', ['section' => 'lesson-plans', 'edit_plan' => $plan->id])
            ->with('status', $request->boolean('submit')
                ? 'Lesson plan submitted to HOD, Academic Registrar, and QA Officer.'
                : 'Lesson plan saved as draft.');
    }

    public function submitLessonPlan(Request $request, LessonPlan $plan): RedirectResponse
    {
        $staff = $this->portalService->staffForUser($request->user());

        try {
            $this->teaching->submitLessonPlan($plan, $staff);
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) {
            return back()->withErrors(['lesson_plan' => $exception->getMessage()]);
        }

        return back()->with('status', 'Lesson plan submitted to HOD, Academic Registrar, and QA Officer.');
    }

    public function verifyLessonPlan(Request $request, LessonPlan $plan): RedirectResponse
    {
        $staff = $this->portalService->staffForUser($request->user());
        $this->teaching->verifyLessonPlan($plan, $staff);

        return back()->with('status', 'Lesson plan verified. You can now submit it for approval.');
    }

    public function updateLessonPlan(Request $request, LessonPlan $plan): RedirectResponse
    {
        $staff = $this->portalService->staffForUser($request->user());
        abort_unless((int) $plan->prepared_by === (int) $staff->id, 403);

        $sourceType = $plan->source_type === 'upload' ? 'upload' : 'form';

        $validated = $request->validate(array_merge(
            $this->lessonPlanFieldRules($sourceType, true),
            $sourceType === 'upload' && $request->hasFile('document')
                ? ['document' => ['required', 'file', 'max:'.config('tich-lesson-plans.upload.max_kb', 10240), 'mimes:pdf,doc,docx']]
                : [],
        ));

        $planData = $this->lessonPlanDocuments->mapValidatedPlanData($validated, $sourceType);

        if ($sourceType === 'form') {
            abort_if(
                ($planData['form_payload']['session_rows'] ?? []) === [],
                422,
                'Add at least one row to the lesson session plan table.'
            );
        }

        if ($sourceType === 'upload' && $request->hasFile('document')) {
            $planData = array_merge($planData, $this->lessonPlanDocuments->storeUpload($staff, $request->file('document')));
        }

        $this->teaching->updateLessonPlan($plan, $staff, $planData);

        return redirect()->route('staff.dashboard', ['section' => 'lesson-plans', 'edit_plan' => $plan->id])
            ->with('status', 'Lesson plan updated.');
    }

    /**
     * @return array<string, mixed>
     */
    private function lessonPlanFieldRules(string $sourceType, bool $updating): array
    {
        $rules = [
            'allocation_id' => $updating ? ['sometimes', 'integer'] : ['required', 'integer'],
            'lesson_title' => ['required', 'string', 'max:255'],
            'planned_date' => ['required', 'date'],
            'week_number' => ['nullable', 'integer', 'min:1'],
            'contact_hours' => ['required', 'integer', 'min:1'],
            'topics_covered' => ['nullable', 'string'],
            'competencies_targeted' => ['nullable', 'string'],
            'teaching_methods' => ['nullable', 'string', 'max:500'],
            'resources_required' => ['nullable', 'string', 'max:500'],
            'submit' => ['nullable', 'boolean'],
        ];

        if ($sourceType === 'form') {
            $rules['lesson_objectives'] = ['required', 'string'];
            $rules['competencies_targeted'] = ['required', 'string'];
            $rules['resources_required'] = ['required', 'string'];
            foreach (array_keys(config('tich-lesson-plans.form_fields', [])) as $field) {
                if (in_array($field, ['assignment', 'references'], true)) {
                    $rules[$field] = ['nullable', 'string'];
                } else {
                    $rules[$field] = ['required', 'string'];
                }
            }
            $rules['session_rows'] = ['required', 'array'];
            foreach (array_keys(config('tich-lesson-plans.session_row_columns', [])) as $column) {
                $rules['session_rows.*.'.$column] = ['nullable', 'string', 'max:5000'];
            }
        } else {
            $rules['lesson_objectives'] = ['nullable', 'string'];
        }

        return $rules;
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
            'signed_sheet' => ['required', 'file', 'mimes:jpg,jpeg,png,webp'],
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
            'class_photo' => ['required', 'file', 'mimes:jpg,jpeg,png,webp'],
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

    public function saveGradingGrid(Request $request): RedirectResponse|JsonResponse
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

        return $this->gradingSaveResponse($request, $allocation->id, 'Competency spreadsheet saved. Cumulative scores updated.');
    }

    public function saveExamMarks(Request $request): RedirectResponse|JsonResponse
    {
        $staff = $this->portalService->staffForUser($request->user());
        $allocation = UnitAllocation::query()->findOrFail($request->integer('allocation_id'));

        $validated = $request->validate([
            'allocation_id' => ['required', 'integer'],
            'exam_max' => ['nullable', 'numeric', 'min:1', 'max:100'],
            'exam_scores' => ['nullable', 'array'],
        ]);

        $this->examMarks->save(
            $allocation,
            $staff,
            $validated['exam_scores'] ?? [],
            (float) ($validated['exam_max'] ?? 100),
        );

        return $this->gradingSaveResponse($request, $allocation->id, 'Exam marks saved and final grades updated.');
    }

    public function storeObjectiveAssessment(Request $request): RedirectResponse
    {
        $staff = $this->portalService->staffForUser($request->user());
        $allocation = UnitAllocation::query()->findOrFail($request->integer('allocation_id'));

        $validated = $request->validate([
            'allocation_id' => ['required', 'integer'],
            'name' => ['required', 'string', 'max:200'],
            'assessment_type' => ['required', 'string', 'in:mcq,true_false,matching,essay,long_answer'],
            'max_score' => ['required', 'numeric', 'min:1'],
            'time_limit_minutes' => ['nullable', 'integer', 'min:1', 'max:480'],
            'available_from' => ['nullable', 'date'],
            'available_until' => ['nullable', 'date', 'after_or_equal:available_from'],
            'show_results_immediately' => ['nullable', 'boolean'],
            'allow_multiple_attempts' => ['nullable', 'boolean'],
            'max_attempts' => ['nullable', 'integer', 'min:1'],
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

    public function saveObjectiveResponses(Request $request): RedirectResponse|JsonResponse
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

        return $this->gradingSaveResponse(
            $request,
            $allocation->id,
            'Student responses saved.',
            ['objective_assessment' => $assessment->id],
        );
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

    public function manualGradeObjectiveSubmission(Request $request): RedirectResponse
    {
        $staff = $this->portalService->staffForUser($request->user());
        $allocation = UnitAllocation::query()->findOrFail($request->integer('allocation_id'));
        $assessment = ObjectiveAssessment::query()->with(['questions'])->findOrFail($request->integer('objective_assessment_id'));

        $validated = $request->validate([
            'allocation_id' => ['required', 'integer'],
            'objective_assessment_id' => ['required', 'integer'],
            'student_id' => ['required', 'integer'],
            'submission_id' => ['required', 'integer'],
            'marks' => ['nullable', 'array'],
            'marks.*' => ['nullable', 'numeric', 'min:0'],
            'score_obtained' => ['required', 'numeric', 'min:0'],
            'feedback' => ['nullable', 'string', 'max:2000'],
        ]);

        $submission = \App\Models\ObjectiveSubmission::query()
            ->where('id', $validated['submission_id'])
            ->where('student_id', $validated['student_id'])
            ->where('objective_assessment_id', $assessment->id)
            ->firstOrFail();

        $totalScore = 0.0;
        if (! empty($validated['marks'])) {
            foreach ($validated['marks'] as $questionId => $mark) {
                $totalScore += (float) ($mark ?? 0);
            }
        }

        $score = $totalScore > 0 ? $totalScore : (float) ($validated['score_obtained'] ?? 0);
        $percentage = $assessment->max_score > 0 ? round((($score / $assessment->max_score) * 100), 2) : 0;

        $submission->update([
            'score_obtained' => $score,
            'percentage_score' => $percentage,
            'correct_count' => 0,
            'question_count' => $assessment->questions->count(),
            'updated_at' => now(),
        ]);

        \App\Models\CatScore::query()->updateOrCreate(
            [
                'student_id' => $submission->student_id,
                'unit_id' => $assessment->unit_id,
                'semester_id' => $assessment->semester_id,
                'assessment_name' => $assessment->name,
            ],
            [
                'assessment_type' => 'objective_'.$assessment->assessment_type,
                'max_score' => $assessment->max_score,
                'score_obtained' => $score,
                'percentage_score' => $percentage,
                'weight_in_final' => $this->assessments->weightForAssessmentType('cat', $assessment->unit),
                'recorded_by' => $staff->id,
                'recorded_at' => now(),
                'updated_at' => now(),
            ]
        );

        $this->portalService->auditService->log(
            'staff.grading.objective_manual_grade',
            'objective_submissions',
            $submission->id,
            null,
            [
                'score_obtained' => $score,
                'feedback' => $validated['feedback'],
            ],
            'Objective assessment manually graded',
            'success',
            $staff->user_id ?? $request->user()->id,
            $request
        );

        return redirect()->route('staff.dashboard', [
            'section' => 'grading',
            'allocation' => $allocation->id,
            'objective_assessment' => $assessment->id,
        ])->with('status', 'Submission graded successfully.');
    }

    public function storeContent(Request $request): RedirectResponse
    {
        $staff = $this->portalService->staffForUser($request->user());

        $validated = $request->validate([
            'unit_id' => ['required', 'integer'],
            'title' => ['required', 'string', 'max:300'],
            'content_text' => ['nullable', 'string', 'max:5000'],
            'file' => ['nullable', 'file', 'max:20480'],
            'available_from' => ['nullable', 'date'],
            'available_until' => ['nullable', 'date', 'after_or_equal:available_from'],
        ]);

        $filePath = null;
        $originalFilename = null;
        $mimeType = null;
        $fileSize = null;

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $filePath = $this->files->store($file, 'learning-content', 'public');
            $originalFilename = $file->getClientOriginalName();
            $mimeType = $file->getMimeType();
            $fileSize = $file->getSize();
        }

        $allocation = UnitAllocation::query()
            ->where('staff_id', $staff->id)
            ->where('unit_id', (int) $validated['unit_id'])
            ->where('is_active', 1)
            ->first();

        UnitContent::query()->create([
            'unit_id' => (int) $validated['unit_id'],
            'unit_allocation_id' => $allocation?->id,
            'created_by' => $staff->id,
            'title' => $validated['title'],
            'content_type' => $filePath ? 'document' : 'lesson_note',
            'content_text' => $validated['content_text'],
            'file_path' => $filePath,
            'original_filename' => $originalFilename,
            'mime_type' => $mimeType,
            'file_size' => $fileSize,
            'status' => 'published',
            'published_at' => now(),
            'available_from' => isset($validated['available_from']) ? now()->parse($validated['available_from']) : null,
            'available_until' => isset($validated['available_until']) ? now()->parse($validated['available_until']) : null,
            'display_order' => 0,
        ]);

        return redirect()->route('staff.dashboard', ['section' => 'content'])
            ->with('status', 'Learning material posted to students.');
    }

    /**
     * @param  array<string, mixed>  $query
     */
    private function gradingSaveResponse(Request $request, int $allocationId, string $message, array $query = []): RedirectResponse|JsonResponse
    {
        if ($this->isAutosaveRequest($request)) {
            return response()->json([
                'ok' => true,
                'message' => $message,
                'saved_at' => now()->toIso8601String(),
            ]);
        }

        return redirect()->route('staff.dashboard', array_merge([
            'section' => 'grading',
            'allocation' => $allocationId,
        ], $query))->with('status', $message);
    }

    private function isAutosaveRequest(Request $request): bool
    {
        return $request->header('X-Auto-Save') === '1'
            || $request->expectsJson()
            || $request->ajax();
    }

    public function updateObjectiveAvailability(Request $request, ObjectiveAssessment $assessment): RedirectResponse
    {
        $staff = $this->portalService->staffForUser($request->user());

        $allocation = UnitAllocation::query()
            ->where('staff_id', $staff->id)
            ->where('unit_id', $assessment->unit_id)
            ->where('is_active', 1)
            ->firstOrFail();

        abort_if($assessment->unit_allocation_id !== $allocation->id, 403);

        $validated = $request->validate([
            'available_from' => ['nullable', 'date'],
            'available_until' => ['nullable', 'date', 'after_or_equal:available_from'],
        ]);

        $assessment->update([
            'available_from' => isset($validated['available_from']) ? now()->parse($validated['available_from']) : null,
            'available_until' => isset($validated['available_until']) ? now()->parse($validated['available_until']) : null,
        ]);

        return redirect()->route('staff.dashboard', [
            'section' => 'grading',
            'allocation' => $allocation->id,
            'objective_assessment' => $assessment->id,
        ])->with('status', 'Assessment availability updated.');
    }

    public function updateObjectiveAnswers(Request $request, ObjectiveAssessment $assessment): RedirectResponse
    {
        $staff = $this->portalService->staffForUser($request->user());

        $allocation = UnitAllocation::query()
            ->where('staff_id', $staff->id)
            ->where('unit_id', $assessment->unit_id)
            ->where('is_active', 1)
            ->firstOrFail();

        abort_if($assessment->unit_allocation_id !== $allocation->id, 403);

        $validated = $request->validate([
            'allocation_id' => ['required', 'integer'],
            'objective_assessment_id' => ['required', 'integer'],
            'correct_answers' => ['nullable', 'array'],
            'correct_answers.*' => ['nullable', 'string', 'max:1000'],
        ]);

        foreach ($validated['correct_answers'] ?? [] as $questionId => $answer) {
            \App\Models\ObjectiveQuestion::query()
                ->where('objective_assessment_id', $assessment->id)
                ->where('id', (int) $questionId)
                ->update(['correct_answer' => (string) $answer]);
        }

        return redirect()->route('staff.dashboard', [
            'section' => 'grading',
            'allocation' => $allocation->id,
            'objective_assessment' => $assessment->id,
        ])->with('status', 'Answer key updated. You can now click Mark to grade all students.');
    }
}
