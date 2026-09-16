<?php

namespace App\Http\Controllers\Qa;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Qa\QaDepartmentSubmission;
use App\Models\Qa\QaPlan;
use App\Services\Qa\QaAssessmentService;
use App\Support\QaTaskModuleContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TaskController extends Controller
{
    public function __construct(protected QaAssessmentService $qa) {}

    public function index(Request $request): View
    {
        $moduleContext = QaTaskModuleContext::resolve($request);
        $tasks = $this->qa->outstandingTasksForUser($request->user());

        return view('qa.tasks.index', $this->viewPayload($moduleContext, [
            'tasks' => $tasks,
            'departments' => $this->qa->respondableDepartments($request->user()),
        ]));
    }

    public function show(Request $request, QaPlan $plan): View
    {
        $moduleContext = QaTaskModuleContext::resolve($request);
        $respondent = $this->respondentDepartment($request);

        abort_unless($plan->isOpenForSubmission(), 404);
        abort_unless(in_array((int) $respondent->id, $plan->targetDepartmentIds(), true), 404);
        abort_unless($this->qa->userCanRespondForDepartment($request->user(), $respondent), 403);

        $plan->load('checklists');
        $submissions = QaDepartmentSubmission::query()
            ->where('qa_plan_id', $plan->id)
            ->where('department_id', $respondent->id)
            ->with('evidence')
            ->get()
            ->keyBy('checklist_item_id');

        $activeItems = $plan->checklists->where('is_active', true);
        $readOnly = $activeItems->isNotEmpty()
            && $activeItems->every(function ($item) use ($submissions) {
                $submission = $submissions->get($item->id);

                return $submission && ! $submission->isEditable();
            });

        return view('qa.tasks.show', $this->viewPayload($moduleContext, [
            'plan' => $plan,
            'respondentDepartment' => $respondent,
            'submissions' => $submissions,
            'readOnly' => $readOnly,
        ]));
    }

    public function store(Request $request, QaPlan $plan): RedirectResponse
    {
        $moduleContext = QaTaskModuleContext::resolve($request);
        $respondent = $this->respondentDepartment($request);

        $hasEditable = QaDepartmentSubmission::query()
            ->where('qa_plan_id', $plan->id)
            ->where('department_id', $respondent->id)
            ->whereIn('submission_status', ['pending', 'draft', 'rejected'])
            ->exists();

        $hasRows = QaDepartmentSubmission::query()
            ->where('qa_plan_id', $plan->id)
            ->where('department_id', $respondent->id)
            ->exists();

        if ($hasRows && ! $hasEditable) {
            return redirect()
                ->to(QaTaskModuleContext::url('show', $moduleContext['key'], [
                    'plan' => $plan,
                    'department' => $respondent,
                    'targetDepartment' => $respondent,
                ]))
                ->withErrors(['task' => 'This assessment has already been submitted and can only be viewed.']);
        }

        $validated = $request->validate([
            'answers' => ['required', 'array'],
            'answers.*.submission_text' => ['nullable', 'string', 'max:5000'],
            'answers.*.score' => ['nullable', 'integer', 'min:0', 'max:100'],
            'evidence' => ['nullable', 'array'],
            'evidence.*' => ['nullable', 'array'],
            'evidence.*.*' => ['file', 'max:10240', 'mimes:jpg,jpeg,png,gif,webp,pdf,doc,docx,xls,xlsx'],
            'save_action' => ['nullable', 'in:draft,submit'],
        ]);

        $finalSubmit = ($validated['save_action'] ?? $request->input('save_action')) === 'submit';

        try {
            $this->qa->saveDepartmentResponses(
                $request->user(),
                $plan,
                $respondent,
                $validated['answers'] ?? [],
                $request->file('evidence', []) ?? [],
                $finalSubmit,
            );
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            return back()->withInput()->withErrors(['task' => $e->getMessage()]);
        }

        return redirect()
            ->to(QaTaskModuleContext::url('show', $moduleContext['key'], [
                'plan' => $plan,
                'department' => $respondent,
                'targetDepartment' => $respondent,
            ]))
            ->with('status', $finalSubmit
                ? 'Assessment submitted to Quality Assurance.'
                : 'Draft saved.');
    }

    private function respondentDepartment(Request $request): Department
    {
        $candidate = $request->route('targetDepartment') ?? $request->route('department');

        // Academics injects the hub as {department}; respondent is {targetDepartment}.
        if ($request->routeIs('departments.academics.qa.tasks.*')) {
            $candidate = $request->route('targetDepartment');
        }

        if ($candidate instanceof Department) {
            return $candidate;
        }

        if (is_string($candidate) || is_numeric($candidate)) {
            $resolved = Department::resolveFromRouteKey((string) $candidate);
            if ($resolved) {
                return $resolved;
            }
        }

        abort(404);
    }

    /**
     * @param  array{key: string, layout: string, content_section: string, routes: array<string, string>, department?: Department|null}  $moduleContext
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    private function viewPayload(array $moduleContext, array $extra = []): array
    {
        $payload = array_merge([
            'moduleContext' => $moduleContext,
            'taskRoutes' => $moduleContext['routes'],
        ], $extra);

        // Academics layout sidebar expects the hub as $department.
        if (($moduleContext['key'] ?? '') === 'academics') {
            $payload['department'] = $moduleContext['department'] ?? Department::findAcademicsHub();
        }

        return $payload;
    }
}
