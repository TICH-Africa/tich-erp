<?php

namespace App\Http\Controllers\Qa;

use App\Http\Controllers\Controller;
use App\Models\Qa\QaCorrectiveAction;
use App\Services\Qa\QaAssessmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CorrectiveActionController extends Controller
{
    public function __construct(protected QaAssessmentService $qa) {}

    public function index(): View
    {
        $actions = QaCorrectiveAction::query()
            ->with(['plan', 'department', 'responsibleOfficer'])
            ->orderByRaw("CASE status WHEN 'open' THEN 1 WHEN 'in_progress' THEN 2 WHEN 'overdue' THEN 3 WHEN 'resolved' THEN 4 ELSE 5 END")
            ->orderBy('resolution_deadline')
            ->paginate(25);

        return view('qa.corrective-actions.index', compact('actions'));
    }

    public function resolve(Request $request, QaCorrectiveAction $action): RedirectResponse
    {
        $validated = $request->validate([
            'resolution_notes' => ['required', 'string', 'max:5000'],
        ]);

        $this->qa->resolveCorrectiveAction($request->user(), $action, $validated['resolution_notes']);

        return back()->with('status', 'Corrective action marked as resolved.');
    }
}
