<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\ObjectiveAssessment;
use App\Models\ObjectiveSubmission;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class StudentAssessmentController extends Controller
{
    public function index(): View
    {
        $student = auth()->user()->student;
        abort_if(! $student, 404);

        $allocations = \App\Models\UnitAllocation::query()
            ->with(['unit', 'semester.academicYear'])
            ->whereHas('unit', fn ($q) => $q->where('department_id', $student->program->department_id ?? null))
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
        abort_if($assessment->status !== 'ready', 403);

        $submission = ObjectiveSubmission::query()
            ->where('objective_assessment_id', $assessment->id)
            ->where('student_id', $student->id)
            ->first();

        if ($submission && $submission->student_submitted_at) {
            return redirect()->route('portal.assessments.result', ['assessment' => $assessment->id])
                ->with('info', 'You have already completed this assessment.');
        }

        if ($assessment->available_from && now()->lt($assessment->available_from)) {
            abort(403, 'This assessment is not yet available.');
        }

        if ($assessment->available_until && now()->gt($assessment->available_until)) {
            abort(403, 'This assessment has expired.');
        }

        return view('portal.assessments.take', [
            'student' => $student,
            'assessment' => $assessment->load('questions'),
            'submission' => $submission,
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
        abort_if($assessment->status !== 'ready', 403);

        $submission = ObjectiveSubmission::query()
            ->where('objective_assessment_id', $assessment->id)
            ->where('student_id', $student->id)
            ->first();

        if ($submission && $submission->student_submitted_at) {
            return redirect()->route('portal.assessments.result', ['assessment' => $assessment->id])
                ->with('info', 'You have already completed this assessment.');
        }

        $validated = $request->validate([
            'responses' => 'required|array',
            'responses.*' => 'nullable|string',
            'time_taken_seconds' => 'nullable|integer|min:0',
        ]);

        $questions = $assessment->questions;
        $totalPoints = (float) $questions->sum('points');

        $gradeResult = app(\App\Services\ObjectiveAutoGradingService::class)->gradeSubmission(
            $questions,
            $validated['responses'],
            $totalPoints,
            (float) $assessment->max_score
        );

        if (! $submission) {
            $submission = ObjectiveSubmission::query()->create([
                'objective_assessment_id' => $assessment->id,
                'student_id' => $student->id,
            ]);
        }

        $submission->update([
            'responses' => $validated['responses'],
            'score_obtained' => $gradeResult['score_obtained'],
            'percentage_score' => $gradeResult['percentage_score'],
            'correct_count' => $gradeResult['correct_count'],
            'question_count' => $gradeResult['question_count'],
            'student_submitted_at' => now(),
            'time_taken_seconds' => $validated['time_taken_seconds'] ?? null,
            'updated_at' => now(),
        ]);

        return redirect()->route('portal.assessments.result', ['assessment' => $assessment->id])
            ->with('status', 'Assessment submitted successfully.');
    }

    public function result(ObjectiveAssessment $assessment): View
    {
        $student = auth()->user()->student;
        abort_if(! $student, 404);

        $submission = ObjectiveSubmission::query()
            ->where('objective_assessment_id', $assessment->id)
            ->where('student_id', $student->id)
            ->firstOrFail();

        $assessment->load('questions');

        return view('portal.assessments.result', [
            'student' => $student,
            'assessment' => $assessment,
            'submission' => $submission,
            'biodata' => [],
            'sidebarNavigation' => app(\App\Services\StudentPortalNavigationService::class)->sidebarNavigation($student),
            'section' => 'academics',
            'tab' => 'assessments',
            'portalTitle' => 'Assessment Result',
        ]);
    }
}
