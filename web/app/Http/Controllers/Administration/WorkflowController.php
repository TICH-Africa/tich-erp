<?php

namespace App\Http\Controllers\Administration;

use App\Http\Controllers\Controller;
use App\Models\Administration\AdminTask;
use App\Models\Administration\CalendarEvent;
use App\Models\Administration\PlanningCycle;
use App\Models\Administration\PlanningVariance;
use App\Models\Department;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class WorkflowController extends Controller
{
    public function index(Request $request): View
    {
        $department = $this->department($request);
        $cycles = PlanningCycle::query()->orderByDesc('period_start')->limit(30)->get();
        $events = CalendarEvent::query()->where('fiscal_year', now()->year)->orderBy('starts_on')->get();
        $tasks = AdminTask::query()->with('department')->when($department, fn ($q) => $q->where('department_id', $department->id))->orderBy('due_on')->limit(50)->get();
        $variances = PlanningVariance::query()->with('department')->when($department, fn ($q) => $q->where('department_id', $department->id))->latest()->limit(30)->get();

        return view('administration.workflow.index', compact('department', 'cycles', 'events', 'tasks', 'variances'));
    }

    public function storeEvent(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'fiscal_year' => ['required', 'integer', 'min:2020', 'max:2100'],
            'event_type' => ['required', 'in:intake,trimester,holiday,graduation,field_placement'],
            'title' => ['required', 'string', 'max:300'],
            'starts_on' => ['required', 'date'],
            'ends_on' => ['nullable', 'date', 'after_or_equal:starts_on'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
        CalendarEvent::query()->create($data + ['created_by' => $request->user()->id]);

        return back()->with('status', 'Annual plan calendar event saved.');
    }

    public function storeTask(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'planning_cycle_id' => ['nullable', 'exists:admin_planning_cycles,id'],
            'department_id' => ['required', 'exists:departments,id'],
            'title' => ['required', 'string', 'max:300'],
            'description' => ['nullable', 'string', 'max:3000'],
            'owner_id' => ['nullable', 'exists:users,id'],
            'due_on' => ['required', 'date'],
            'budget_implication' => ['nullable', 'numeric', 'min:0'],
        ]);
        abort_unless($this->isAdministrativeDepartment((int) $data['department_id']), 403);
        AdminTask::query()->create($data + ['status' => 'pending']);

        return back()->with('status', 'Weekly task added to the department board.');
    }

    public function completeTask(AdminTask $task): RedirectResponse
    {
        $task->update(['status' => 'completed']);

        return back()->with('status', 'Task marked completed.');
    }

    public function storeVariance(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'planning_cycle_id' => ['nullable', 'exists:admin_planning_cycles,id'],
            'department_id' => ['required', 'exists:departments,id'],
            'fiscal_year' => ['required', 'integer', 'min:2020', 'max:2100'],
            'month' => ['required', 'integer', 'min:1', 'max:12'],
            'planned_amount' => ['required', 'numeric', 'min:0'],
            'actual_amount' => ['required', 'numeric', 'min:0'],
            'explanation' => ['nullable', 'string', 'max:3000'],
            'lessons' => ['nullable', 'string', 'max:3000'],
        ]);
        abort_unless($this->isAdministrativeDepartment((int) $data['department_id']), 403);
        PlanningVariance::query()->create($data + ['reviewed_by' => $request->user()->id]);

        return back()->with('status', 'Monthly variance and lesson saved for the next plan.');
    }

    private function department(Request $request): ?Department
    {
        if (! $request->filled('department')) {
            return null;
        }

        abort_unless(Schema::hasTable('departments') && Schema::hasTable('department_modules'), 404);

        return Department::query()
            ->whereKey((int) $request->query('department'))
            ->where('dept_category', 'administrative')
            ->where('is_active', true)
            ->whereExists(fn ($query) => $query->selectRaw('1')->from('department_modules')->whereColumn('department_modules.department_id', 'departments.id')->where('department_modules.module_key', 'administration'))
            ->firstOrFail();
    }

    private function isAdministrativeDepartment(int $departmentId): bool
    {
        return Department::query()->whereKey($departmentId)->where('dept_category', 'administrative')->where('is_active', true)->whereExists(fn ($query) => $query->selectRaw('1')->from('department_modules')->whereColumn('department_modules.department_id', 'departments.id')->where('department_modules.module_key', 'administration'))->exists();
    }
}