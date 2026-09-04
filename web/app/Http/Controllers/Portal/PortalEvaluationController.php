<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\CourseEvaluation;
use App\Models\CourseEvaluationWindow;
use App\Services\StudentPortalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PortalEvaluationController extends Controller
{
    public function __construct(
        protected StudentPortalService $portalService,
    ) {}

    public function index(Request $request): View|RedirectResponse
    {
        return redirect()->route('portal.dashboard', ['section' => 'evaluations']);
    }

    public function store(Request $request): RedirectResponse
    {
        $student = $this->portalService->studentForUser($request->user());
        abort_if(! $student, 404);

        $validated = $request->validate([
            'window_id' => 'required|exists:course_evaluation_windows,id',
            'unit_id' => 'nullable|integer',
            'rating' => 'required|integer|min:1|max:5',
            'comments' => 'nullable|string|max:5000',
        ]);

        $window = CourseEvaluationWindow::query()->findOrFail($validated['window_id']);
        abort_unless($window->isOpen(), 422, 'This evaluation window is closed.');

        CourseEvaluation::query()->updateOrCreate(
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

        return redirect()
            ->route('portal.dashboard', ['section' => 'evaluations'])
            ->with('success', 'Thank you — your evaluation was submitted.');
    }
}
