<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\ObjectiveAssessment;
use App\Models\ObjectiveSubmission;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class StudentAssessmentController extends Controller
{
    public function index(): View
    {
        $student = auth()->user()->student;
        abort_if(! $student, 404);

        // Only the units this student is registered on, so another year's assessment never
        // appears on their list.
        $allocations = \App\Models\UnitAllocation::query()
            ->with(['unit', 'semester.academicYear'])
            ->whereHas('unit', fn ($q) => $q->where('department_id', $student->program->department_id ?? null))
            ->whereIn('unit_id', app(\App\Services\StudentAcademicRecordService::class)->registeredUnitIds($student))
            ->where('is_active', 1)
            ->get();

        $allocationIds = $allocations->pluck('id')->filter()->unique()->values()->all();

        $assessments = ObjectiveAssessment::query()
            ->with(['allocation.unit', 'questions'])
            ->whereIn('unit_allocation_id', $allocationIds)
            ->whereIn('status', ['ready', 'graded'])
            ->orderByDesc('created_at')
            ->get();

        $mySubmissions = ObjectiveSubmission::query()
            ->where('student_id', $student->id)
            ->whereIn('objective_assessment_id', $assessments->pluck('id'))
            ->get()
            ->keyBy('objective_assessment_id');

        return view('portal.assessments.index', [
            'student' => $student,
            'assessments' => $assessments,
            'mySubmissions' => $mySubmissions,
            'allocations' => $allocations,
            'biodata' => [],
            'sidebarNavigation' => app(\App\Services\StudentPortalNavigationService::class)->sidebarNavigation($student),
            'section' => 'academics',
            'tab' => 'assessments',
            'portalTitle' => 'Assessments',
        ]);
    }

    public function take(ObjectiveAssessment $assessment): View|RedirectResponse
    {
        $student = auth()->user()->student;
        abort_if(! $student, 404);
        $this->assertRegistered($student, $assessment);

        $submission = ObjectiveSubmission::query()
            ->where('objective_assessment_id', $assessment->id)
            ->where('student_id', $student->id)
            ->first();

        if ($submission && $submission->student_submitted_at) {
            return redirect()->route('portal.assessments.result', ['assessment' => $assessment->id])
                ->with('info', 'You have already completed this assessment.');
        }

        abort_unless($assessment->isOpenFor($submission), 403, $this->closedMessage($assessment, $submission));

        // The sitting needs a row of its own before it can carry the student's paper order.
        $submission ??= ObjectiveSubmission::query()->create([
            'objective_assessment_id' => $assessment->id,
            'student_id' => $student->id,
            'created_at' => now(),
        ]);

        $assessment->drawShuffleSeed($submission);
        $questions = $assessment->questionsFor($submission);

        return view('portal.assessments.take', [
            'student' => $student,
            'assessment' => $assessment,
            'submission' => $submission,
            'questions' => $questions,
            'window' => $assessment->windowFor($submission),
            'timeLimit' => $assessment->time_limit_minutes ?: 30,
            'biodata' => [],
            'sidebarNavigation' => app(\App\Services\StudentPortalNavigationService::class)->sidebarNavigation($student),
            'section' => 'academics',
            'tab' => 'assessments',
            'portalTitle' => $assessment->name,
        ]);
    }

    public function submit(Request $request, ObjectiveAssessment $assessment)
    {
        $student = auth()->user()->student;
        abort_if(! $student, 404);
        $this->assertRegistered($student, $assessment);

        $submission = ObjectiveSubmission::query()
            ->where('objective_assessment_id', $assessment->id)
            ->where('student_id', $student->id)
            ->first();

        if ($submission && $submission->student_submitted_at) {
            return redirect()->route('portal.assessments.result', ['assessment' => $assessment->id])
                ->with('info', 'You have already completed this assessment.');
        }

        abort_unless($assessment->isOpenFor($submission), 403, $this->closedMessage($assessment, $submission));

        $validated = $request->validate([
            'responses' => 'required|array',
            'responses.*' => 'nullable|string',
            'time_taken_seconds' => 'nullable|integer|min:0',
        ]);

        // Nothing is marked at submission time. The paper is graded once the availability
        // window closes, so nobody can answer after the deadline and see the key.
        if (! $submission) {
            $submission = ObjectiveSubmission::query()->create([
                'objective_assessment_id' => $assessment->id,
                'student_id' => $student->id,
            ]);
        }

        $submission->update([
            'responses' => $validated['responses'],
            'student_submitted_at' => now(),
            'time_taken_seconds' => $validated['time_taken_seconds'] ?? null,
            'updated_at' => now(),
        ]);

        return redirect()->route('portal.assessments.result', ['assessment' => $assessment->id])
            ->with('status', 'Submitted successfully on '.now()->format('d M Y \a\t H:i').'.');
    }

    public function result(ObjectiveAssessment $assessment): View
    {
        $student = auth()->user()->student;
        abort_if(! $student, 404);

        $submission = ObjectiveSubmission::query()
            ->where('objective_assessment_id', $assessment->id)
            ->where('student_id', $student->id)
            ->firstOrFail();

        $marked = $assessment->isMarkedFor($submission);

        // Questions and the answer key only reach the student after marking, and only when
        // the lecturer switched review on for this paper.
        $canReview = $marked && (bool) $assessment->show_results_immediately;

        return view('portal.assessments.result', [
            'student' => $student,
            'assessment' => $assessment,
            'submission' => $submission,
            'marked' => $marked,
            'canReview' => $canReview,
            'questions' => $canReview ? $assessment->questionsFor($submission) : collect(),
            'biodata' => [],
            'sidebarNavigation' => app(\App\Services\StudentPortalNavigationService::class)->sidebarNavigation($student),
            'section' => 'academics',
            'tab' => 'assessments',
            'portalTitle' => 'Assessment Result',
        ]);
    }

    /**
     * @return string
     */
    private function closedMessage(ObjectiveAssessment $assessment, ?ObjectiveSubmission $submission)
    {
        $window = $assessment->windowFor($submission);

        if ($window['from'] && now()->lt($window['from'])) {
            return 'This assessment is not yet available.';
        }

        return 'This assessment has expired. Ask your lecturer to open a make-up sitting for you.';
    }

    /**
     * An assessment belongs to a unit, and a student only works on units they are registered for.
     * Anyone who has already answered keeps access, even if Academics later un-assigns the unit.
     */
    private function assertRegistered(Student $student, ObjectiveAssessment $assessment): void
    {
        $held = app(\App\Services\StudentAcademicRecordService::class)->registeredUnitIds($student);

        if (in_array((int) ($assessment->allocation?->unit_id ?? 0), $held, true)) {
            return;
        }

        $answered = ObjectiveSubmission::query()
            ->where('objective_assessment_id', $assessment->id)
            ->where('student_id', $student->id)
            ->exists();

        abort_unless($answered, 403, 'You are not registered on this unit yet. Contact your HOD.');
    }
}
