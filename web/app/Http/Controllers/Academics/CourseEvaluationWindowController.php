<?php

namespace App\Http\Controllers\Academics;

use App\Models\CourseEvaluationWindow;
use App\Models\Department;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CourseEvaluationWindowController extends DepartmentAcademicsController
{
    public function index(Request $request, Department $department): View
    {
        $hub = $this->authorizeHub($request, $department);

        $windows = CourseEvaluationWindow::query()
            ->withCount('evaluations')
            ->orderByDesc('opens_at')
            ->paginate(20)
            ->withQueryString();

        return view('academics.evaluation-windows.index', [
            'department' => $hub,
            'windows' => $windows,
        ]);
    }

    public function store(Request $request, Department $department): RedirectResponse
    {
        $this->authorizeHub($request, $department);

        $validated = $request->validate([
            'title' => 'required|string|max:191',
            'semester_id' => 'nullable|integer',
            'opens_at' => 'required|date',
            'closes_at' => 'required|date|after:opens_at',
            'is_active' => 'nullable|boolean',
        ]);

        CourseEvaluationWindow::query()->create([
            'title' => $validated['title'],
            'semester_id' => $validated['semester_id'] ?? null,
            'opens_at' => $validated['opens_at'],
            'closes_at' => $validated['closes_at'],
            'is_active' => $request->boolean('is_active', true),
            'created_by_user_id' => $request->user()->id,
        ]);

        return redirect()
            ->route('departments.academics.evaluation-windows.index', \App\Support\AcademicsRouteParams::fromRequest($request))
            ->with('success', 'Evaluation window created.');
    }

    public function update(Request $request, Department $department, CourseEvaluationWindow $evaluationWindow): RedirectResponse
    {
        $this->authorizeHub($request, $department);

        $validated = $request->validate([
            'title' => 'required|string|max:191',
            'semester_id' => 'nullable|integer',
            'opens_at' => 'required|date',
            'closes_at' => 'required|date|after:opens_at',
            'is_active' => 'nullable|boolean',
        ]);

        $evaluationWindow->update([
            'title' => $validated['title'],
            'semester_id' => $validated['semester_id'] ?? null,
            'opens_at' => $validated['opens_at'],
            'closes_at' => $validated['closes_at'],
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()
            ->route('departments.academics.evaluation-windows.index', \App\Support\AcademicsRouteParams::fromRequest($request))
            ->with('success', 'Evaluation window updated.');
    }
}
