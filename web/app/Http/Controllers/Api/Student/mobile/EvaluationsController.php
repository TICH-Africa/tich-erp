<?php

namespace App\Http\Controllers\Api\Student\mobile;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Student\mobile\Concerns\ResolvesPortalStudent;
use App\Models\CourseEvaluation;
use App\Models\CourseEvaluationWindow;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class EvaluationsController extends Controller
{
    use ResolvesPortalStudent;

    public function index(Request $request): JsonResponse
    {
        $student = $this->portalStudent($request);

        if (! Schema::hasTable('course_evaluation_windows')) {
            return response()->json(['windows' => [], 'my_evaluations' => []]);
        }

        $windows = CourseEvaluationWindow::query()
            ->where('is_active', true)
            ->orderByDesc('opens_at')
            ->get()
            ->filter(fn (CourseEvaluationWindow $window) => $window->isOpen())
            ->values()
            ->map(fn (CourseEvaluationWindow $w) => [
                'id' => $w->id,
                'title' => $w->title,
                'opens_at' => optional($w->opens_at)?->toIso8601String(),
                'closes_at' => optional($w->closes_at)?->toIso8601String(),
                'instructions' => $w->instructions,
            ]);

        $myEvaluations = CourseEvaluation::query()
            ->where('student_id', $student->id)
            ->orderByDesc('submitted_at')
            ->limit(30)
            ->get()
            ->map(fn (CourseEvaluation $e) => [
                'id' => $e->id,
                'window_id' => $e->window_id,
                'unit_id' => $e->unit_id,
                'rating' => $e->rating,
                'comments' => $e->comments,
                'submitted_at' => optional($e->submitted_at)?->toIso8601String(),
            ]);

        return response()->json([
            'windows' => $windows,
            'my_evaluations' => $myEvaluations,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $student = $this->portalStudent($request);

        $validated = $request->validate([
            'window_id' => 'required|exists:course_evaluation_windows,id',
            'unit_id' => 'nullable|integer',
            'rating' => 'required|integer|min:1|max:5',
            'comments' => 'nullable|string|max:5000',
        ]);

        $window = CourseEvaluationWindow::query()->findOrFail($validated['window_id']);
        abort_unless($window->isOpen(), 422, 'This evaluation window is closed.');

        $evaluation = CourseEvaluation::query()->updateOrCreate(
            [
                'window_id' => $window->id,
                'student_id' => $student->id,
                'unit_id' => $validated['unit_id'] ?? null,
            ],
            [
                'rating' => $validated['rating'],
                'comments' => $validated['comments'] ?? null,
                'submitted_at' => now(),
            ]
        );

        return response()->json([
            'message' => 'Thank you — your evaluation was submitted.',
            'evaluation' => [
                'id' => $evaluation->id,
                'rating' => $evaluation->rating,
                'submitted_at' => optional($evaluation->submitted_at)?->toIso8601String(),
            ],
        ]);
    }
}
