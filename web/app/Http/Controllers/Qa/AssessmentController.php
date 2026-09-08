<?php

namespace App\Http\Controllers\Qa;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Qa\QaPlan;
use App\Services\Qa\QaAssessmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AssessmentController extends Controller
{
    public function __construct(protected QaAssessmentService $qa) {}

    public function index(): View
    {
        $plans = QaPlan::query()
            ->withCount(['checklists', 'correctiveActions'])
            ->orderByDesc('id')
            ->paginate(20);

        return view('qa.assessments.index', compact('plans'));
    }

    public function create(): View
    {
        return view('qa.assessments.create', [
            'departments' => Department::query()->active()->orderBy('dept_name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        [$data, $items, $departmentIds] = $this->validated($request);

        $plan = $this->qa->createPlan($request->user(), $data, $items, $departmentIds);

        return redirect()
            ->route('qa.assessments.show', $plan)
            ->with('status', 'Assessment sheet created as draft. Review criteria, then dispatch.');
    }

    public function show(QaPlan $plan): View
    {
        $plan->load(['checklists', 'complianceScores.department', 'correctiveActions.department', 'deployedBy']);

        return view('qa.assessments.show', compact('plan'));
    }

    public function edit(QaPlan $plan): View
    {
        abort_unless($plan->isDraft(), 404);

        $plan->load('checklists');

        return view('qa.assessments.edit', [
            'plan' => $plan,
            'departments' => Department::query()->active()->orderBy('dept_name')->get(),
        ]);
    }

    public function update(Request $request, QaPlan $plan): RedirectResponse
    {
        [$data, $items, $departmentIds] = $this->validated($request);
        $this->qa->updatePlan($request->user(), $plan, $data, $items, $departmentIds);

        return redirect()
            ->route('qa.assessments.show', $plan)
            ->with('status', 'Assessment sheet updated.');
    }

    public function dispatch(Request $request, QaPlan $plan): RedirectResponse
    {
        try {
            $this->qa->dispatchPlan($request->user(), $plan);
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            return back()->withErrors(['dispatch' => $e->getMessage()]);
        }

        return redirect()
            ->route('qa.assessments.show', $plan)
            ->with('status', 'Assessment sheet dispatched. Departments have been notified.');
    }

    public function compile(Request $request, QaPlan $plan): RedirectResponse
    {
        try {
            $this->qa->compilePlan($request->user(), $plan);
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            return back()->withErrors(['compile' => $e->getMessage()]);
        }

        return redirect()
            ->route('qa.assessments.show', $plan)
            ->with('status', 'Quality Level Report compiled and sent to the CEO office.');
    }

    /**
     * @return array{0: array<string, mixed>, 1: list<array<string, mixed>>, 2: list<int>}
     */
    private function validated(Request $request): array
    {
        $validated = $request->validate([
            'plan_name' => ['required', 'string', 'max:300'],
            'description' => ['nullable', 'string', 'max:5000'],
            'instructions' => ['nullable', 'string', 'max:5000'],
            'period_start' => ['required', 'date'],
            'period_end' => ['required', 'date', 'after_or_equal:period_start'],
            'due_at' => ['nullable', 'date'],
            'pass_threshold' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'department_ids' => ['required', 'array', 'min:1'],
            'department_ids.*' => ['integer', 'exists:departments,id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.text' => ['required', 'string', 'max:2000'],
            'items.*.category' => ['nullable', 'string', 'max:100'],
            'items.*.weight' => ['nullable', 'numeric', 'min:0.01', 'max:100'],
            'items.*.max_score' => ['nullable', 'numeric', 'min:1', 'max:100'],
            'items.*.requires_evidence' => ['nullable', 'boolean'],
        ]);

        $items = [];
        foreach ($validated['items'] as $item) {
            $items[] = [
                'text' => $item['text'],
                'category' => $item['category'] ?? null,
                'weight' => $item['weight'] ?? 1,
                'max_score' => $item['max_score'] ?? 100,
                'requires_evidence' => ! empty($item['requires_evidence']),
            ];
        }

        return [
            [
                'plan_name' => $validated['plan_name'],
                'description' => $validated['description'] ?? null,
                'instructions' => $validated['instructions'] ?? null,
                'period_start' => $validated['period_start'],
                'period_end' => $validated['period_end'],
                'due_at' => $validated['due_at'] ?? null,
                'pass_threshold' => $validated['pass_threshold'] ?? 70,
            ],
            $items,
            array_map('intval', $validated['department_ids']),
        ];
    }
}
