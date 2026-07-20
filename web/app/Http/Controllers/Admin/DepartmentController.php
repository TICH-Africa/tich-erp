<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Campus;
use App\Models\Department;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DepartmentController extends Controller
{
    public function __construct(protected AuditService $auditService) {}

    public function index(): View
    {
        $departments = Department::query()
            ->with('campus:id,campus_name')
            ->orderBy('dept_name')
            ->get();

        return view('admin.departments.index', [
            'departments' => $departments,
            'campuses' => Campus::query()->where('is_active', 1)->orderBy('campus_name')->get(['id', 'campus_name']),
            'deptCategories' => ['academic', 'administrative', 'support'],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'dept_code' => ['required', 'string', 'max:20', 'unique:departments,dept_code'],
            'dept_name' => ['required', 'string', 'max:200'],
            'dept_category' => ['required', 'in:academic,administrative,support'],
            'campus_id' => ['nullable', 'exists:campuses,id'],
            'parent_dept_id' => ['nullable', 'exists:departments,id'],
        ]);

        $department = Department::create([
            ...$validated,
            'is_active' => 1,
            'created_by' => $request->user()->id,
        ]);

        $this->auditService->log(
            'core.department.created',
            'departments',
            $department->id,
            null,
            $department->only(['dept_code', 'dept_name', 'dept_category', 'campus_id']),
            null,
            'success',
            $request->user()->id,
            $request
        );

        return back()->with('status', 'Department created successfully.');
    }

    public function update(Request $request, Department $department): RedirectResponse
    {
        $validated = $request->validate([
            'dept_code' => ['required', 'string', 'max:20', 'unique:departments,dept_code,'.$department->id],
            'dept_name' => ['required', 'string', 'max:200'],
            'dept_category' => ['required', 'in:academic,administrative,support'],
            'campus_id' => ['nullable', 'exists:campuses,id'],
            'parent_dept_id' => ['nullable', 'exists:departments,id'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $old = $department->only(['dept_code', 'dept_name', 'dept_category', 'campus_id', 'is_active']);
        $department->update([
            ...$validated,
            'is_active' => $request->boolean('is_active'),
        ]);

        $this->auditService->log(
            'core.department.updated',
            'departments',
            $department->id,
            $old,
            $department->only(['dept_code', 'dept_name', 'dept_category', 'campus_id', 'is_active']),
            null,
            'success',
            $request->user()->id,
            $request
        );

        return back()->with('status', 'Department updated successfully.');
    }
}
