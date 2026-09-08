<?php

namespace App\Http\Controllers\Qa;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Qa\QaDepartmentSubmission;
use App\Models\Qa\QaPlan;
use App\Services\Qa\QaAssessmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TaskController extends Controller
{
    public function __construct(protected QaAssessmentService $qa) {}

    public function index(Request $request): View
    {
        $departments = $this->qa->respondableDepartments($request->user());
        $departmentIds = $departments->pluck('id')->all();

        $plans = QaPlan::query()
            ->whereIn('status', ['dispatched', 'in_progress'])
            ->orderByDesc('dispatched_at')
            ->get()
            ->filter(function (QaPlan $plan) use ($departmentIds) {
                return count(array_intersect($plan->targetDepartmentIds(), $departmentIds)) > 0;
            })
            ->values();

        return view('qa.tasks.index', [
            'plans' => $plans,
            'departments' => $departments,
        ]);
    }

    public function show(Request $request, QaPlan $plan, Department $department): View
    {
        abort_unless($plan->isOpenForSubmission(), 404);
        abort_unless(in_array((int) $department->id, $plan->targetDepartmentIds(), true), 404);
        abort_unless($this->qa->userCanRespondForDepartment($request->user(), $department), 403);

        $plan->load('checklists');
        $submissions = QaDepartmentSubmission::query()
            ->where('qa_plan_id', $plan->id)
            ->where('department_id', $department->id)
            ->with('evidence')
            ->get()
            ->keyBy('checklist_item_id');

        return view('qa.tasks.show', compact('plan', 'department', 'submissions'));
    }

    public function store(Request $request, QaPlan $plan, Department $department): RedirectResponse
    {
        $validated = $request->validate([
            'answers' => ['required', 'array'],
            'answers.*.submission_text' => ['nullable', 'string', 'max:5000'],
            'answers.*.score' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'evidence' => ['nullable', 'array'],
            'evidence.*' => ['nullable', 'array'],
            'evidence.*.*' => ['file', 'max:10240', 'mimes:jpg,jpeg,png,gif,webp,pdf,doc,docx,xls,xlsx'],
            'final_submit' => ['nullable', 'boolean'],
        ]);

        try {
            $this->qa->saveDepartmentResponses(
                $request->user(),
                $plan,
                $department,
                $validated['answers'] ?? [],
                $validated['evidence'] ?? [],
                $request->boolean('final_submit'),
            );
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            return back()->withInput()->withErrors(['task' => $e->getMessage()]);
        }

        return redirect()
            ->route('qa.tasks.show', [$plan, $department])
            ->with('status', $request->boolean('final_submit')
                ? 'Assessment submitted to Quality Assurance.'
                : 'Draft saved.');
    }
}
