<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DepartmentGroup;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DepartmentGroupController extends Controller
{
    public function __construct(protected AuditService $auditService) {}

    public function index(): View
    {
        $groups = DepartmentGroup::query()
            ->withCount('departments')
            ->orderBy('display_order')
            ->orderBy('group_name')
            ->get();

        return view('admin.department-groups.index', compact('groups'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'group_code' => ['required', 'string', 'max:20', 'unique:department_groups,group_code'],
            'group_name' => ['required', 'string', 'max:200'],
            'display_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ]);

        $group = DepartmentGroup::create([
            ...$validated,
            'display_order' => $validated['display_order'] ?? 0,
            'is_active' => 1,
            'created_by' => $request->user()->id,
        ]);

        $this->auditService->log(
            'core.department_group.created',
            'department_groups',
            $group->id,
            null,
            $group->only(['group_code', 'group_name', 'display_order']),
            null,
            'success',
            $request->user()->id,
            $request
        );

        return back()->with('status', 'Department group created successfully.');
    }

    public function update(Request $request, DepartmentGroup $departmentGroup): RedirectResponse
    {
        $validated = $request->validate([
            'group_code' => ['required', 'string', 'max:20', 'unique:department_groups,group_code,'.$departmentGroup->id],
            'group_name' => ['required', 'string', 'max:200'],
            'display_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $old = $departmentGroup->only(['group_code', 'group_name', 'display_order', 'is_active']);
        $departmentGroup->update([
            ...$validated,
            'display_order' => $validated['display_order'] ?? 0,
            'is_active' => $request->boolean('is_active'),
        ]);

        $this->auditService->log(
            'core.department_group.updated',
            'department_groups',
            $departmentGroup->id,
            $old,
            $departmentGroup->only(['group_code', 'group_name', 'display_order', 'is_active']),
            null,
            'success',
            $request->user()->id,
            $request
        );

        return back()->with('status', 'Department group updated successfully.');
    }
}
