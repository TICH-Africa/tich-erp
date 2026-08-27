<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Campus;
use App\Models\Department;
use App\Models\DepartmentGroup;
use App\Services\AuditService;
use App\Services\DepartmentModuleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DepartmentController extends Controller
{
    public function __construct(
        protected AuditService $auditService,
        protected DepartmentModuleService $departmentModuleService,
    ) {}

    public function index(): View
    {
        $groups = DepartmentGroup::query()
            ->with([
                'departments' => fn ($query) => $query
                    ->with(['campus:id,campus_name', 'parent:id,dept_name', 'children' => fn ($q) => $q->with('campus:id,campus_name')->orderBy('display_order')->orderBy('dept_name')])
                    ->whereNull('parent_dept_id')
                    ->orderBy('display_order')
                    ->orderBy('dept_name'),
            ])
            ->orderBy('display_order')
            ->orderBy('group_name')
            ->get();

        $ungrouped = Department::query()
            ->with(['campus:id,campus_name', 'parent:id,dept_name', 'children'])
            ->whereNull('department_group_id')
            ->whereNull('parent_dept_id')
            ->orderBy('dept_name')
            ->get();

        $allDepartments = Department::query()
            ->orderBy('dept_name')
            ->get(['id', 'dept_code', 'dept_name', 'dept_category', 'department_group_id', 'parent_dept_id', 'campus_id', 'display_order', 'is_active']);

        $moduleAssignments = $this->departmentModuleService->assignedModulesByDepartmentIds(
            $allDepartments->pluck('id')->all()
        );

        return view('admin.departments.index', [
            'groups' => $groups,
            'ungrouped' => $ungrouped,
            'allDepartments' => $allDepartments,
            'moduleAssignments' => $moduleAssignments,
            'moduleCatalog' => $this->departmentModuleService->catalog(),
            'modulesTableReady' => Schema::hasTable('department_modules'),
            'campuses' => Campus::query()->where('is_active', 1)->orderBy('campus_name')->get(['id', 'campus_name']),
            'departmentGroups' => DepartmentGroup::query()->where('is_active', 1)->orderBy('display_order')->get(['id', 'group_name', 'group_code']),
            'parentDepartments' => Department::query()
                ->whereNull('parent_dept_id')
                ->where('is_active', 1)
                ->whereIn('id', $this->departmentModuleService->departmentIdsHostingLearningDepartments())
                ->orderBy('dept_name')
                ->get(['id', 'dept_name', 'dept_code', 'department_group_id']),
            'deptCategories' => [
                'administrative' => 'Administrative unit',
                'academic' => 'Academic department',
                'support' => 'Support',
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        if ($redirect = $this->ensureModulesStorageReady()) {
            return $redirect;
        }

        $validKeys = $this->departmentModuleService->validModuleKeys();

        if ($validKeys === []) {
            return back()->withInput()->withErrors([
                'module_keys' => 'Platform module catalog is empty. Deploy config/tich-department-modules.php and clear config cache.',
            ]);
        }

        $validated = $request->validate([
            'dept_code' => ['required', 'string', 'max:20', 'unique:departments,dept_code'],
            'dept_name' => ['required', 'string', 'max:200'],
            'dept_category' => ['required', 'in:academic,administrative,support'],
            'department_group_id' => ['nullable', 'exists:department_groups,id'],
            'campus_id' => ['nullable', 'exists:campuses,id'],
            'parent_dept_id' => ['nullable', 'exists:departments,id'],
            'display_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'module_keys' => ['nullable', 'array'],
            'module_keys.*' => ['string', Rule::in($validKeys)],
        ]);

        $submittedKeys = $request->input('module_keys');
        $moduleKeys = is_array($submittedKeys)
            ? $submittedKeys
            : $this->departmentModuleService->defaultModulesForCategory($validated['dept_category']);
        $moduleKeys = $this->departmentModuleService->filterKeysForCategory(
            $validated['dept_category'],
            $moduleKeys
        );

        if (is_array($submittedKeys) && $submittedKeys !== [] && $moduleKeys === []) {
            return back()->withInput()->withErrors([
                'module_keys' => 'None of the selected modules are valid for this department category.',
            ]);
        }

        $hierarchyErrors = $this->departmentModuleService->learningDepartmentHierarchyErrors(
            array_merge($validated, ['module_keys' => $moduleKeys])
        );
        if ($hierarchyErrors !== []) {
            return back()->withInput()->withErrors($hierarchyErrors);
        }

        unset($validated['module_keys']);

        try {
            $department = DB::transaction(function () use ($request, $validated, $moduleKeys) {
                $department = Department::create([
                    ...$validated,
                    'display_order' => $validated['display_order'] ?? 0,
                    'is_active' => 1,
                    'created_by' => $request->user()->id,
                ]);

                $this->departmentModuleService->syncModules($department, $moduleKeys, $request->user()->id);

                return $department;
            });
        } catch (\Throwable $e) {
            report($e);

            return back()->withInput()->withErrors([
                'module_keys' => 'Department was not saved because module assignment failed: '.$e->getMessage(),
            ]);
        }

        $this->auditService->log(
            'core.department.created',
            'departments',
            $department->id,
            null,
            [
                ...$department->only(['dept_code', 'dept_name', 'dept_category', 'department_group_id', 'parent_dept_id']),
                'module_keys' => $moduleKeys,
            ],
            null,
            'success',
            $request->user()->id,
            $request
        );

        $moduleCount = count($moduleKeys);

        return back()->with(
            'status',
            $moduleCount > 0
                ? "Department created with {$moduleCount} module(s) assigned."
                : 'Department created successfully (no modules assigned).'
        );
    }

    public function update(Request $request, Department $department): RedirectResponse
    {
        if ($redirect = $this->ensureModulesStorageReady()) {
            return $redirect;
        }

        $validKeys = $this->departmentModuleService->validModuleKeys();

        if ($validKeys === []) {
            return back()->withInput()->withErrors([
                'module_keys' => 'Platform module catalog is empty. Deploy config/tich-department-modules.php and clear config cache.',
            ]);
        }

        $validated = $request->validate([
            'dept_code' => ['required', 'string', 'max:20', 'unique:departments,dept_code,'.$department->id],
            'dept_name' => ['required', 'string', 'max:200'],
            'dept_category' => ['required', 'in:academic,administrative,support'],
            'department_group_id' => ['nullable', 'exists:department_groups,id'],
            'campus_id' => ['nullable', 'exists:campuses,id'],
            'parent_dept_id' => ['nullable', 'exists:departments,id', 'not_in:'.$department->id],
            'display_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['nullable', 'boolean'],
            'module_keys' => ['nullable', 'array'],
            'module_keys.*' => ['string', Rule::in($validKeys)],
        ]);

        if (! empty($validated['parent_dept_id']) && $this->isDescendant((int) $validated['parent_dept_id'], $department->id)) {
            return back()->withInput()->withErrors(['parent_dept_id' => 'Cannot assign a descendant department as parent.']);
        }

        $submittedKeys = $request->input('module_keys');
        $moduleKeys = $this->departmentModuleService->filterKeysForCategory(
            $validated['dept_category'],
            is_array($submittedKeys) ? $submittedKeys : []
        );

        if (is_array($submittedKeys) && $submittedKeys !== [] && $moduleKeys === []) {
            return back()->withInput()->withErrors([
                'module_keys' => 'None of the selected modules are valid for this department category.',
            ]);
        }

        $hierarchyErrors = $this->departmentModuleService->learningDepartmentHierarchyErrors(
            array_merge($validated, ['module_keys' => $moduleKeys]),
            $department,
        );
        if ($hierarchyErrors !== []) {
            return back()->withInput()->withErrors($hierarchyErrors);
        }

        unset($validated['module_keys']);

        $old = [
            ...$department->only(['dept_code', 'dept_name', 'dept_category', 'department_group_id', 'parent_dept_id', 'is_active']),
            'module_keys' => $this->departmentModuleService->assignedModuleKeys($department),
        ];

        try {
            DB::transaction(function () use ($request, $department, $validated, $moduleKeys) {
                $department->update([
                    ...$validated,
                    'display_order' => $validated['display_order'] ?? 0,
                    'is_active' => $request->boolean('is_active'),
                ]);

                $this->departmentModuleService->syncModules($department, $moduleKeys, $request->user()->id);
            });
        } catch (\Throwable $e) {
            report($e);

            return back()->withInput()->withErrors([
                'module_keys' => 'Changes were not saved because module assignment failed: '.$e->getMessage(),
            ]);
        }

        $this->auditService->log(
            'core.department.updated',
            'departments',
            $department->id,
            $old,
            [
                ...$department->only(['dept_code', 'dept_name', 'dept_category', 'department_group_id', 'parent_dept_id', 'is_active']),
                'module_keys' => $moduleKeys,
            ],
            null,
            'success',
            $request->user()->id,
            $request
        );

        $moduleCount = count($moduleKeys);

        return back()->with(
            'status',
            $moduleCount > 0
                ? "Department updated with {$moduleCount} module(s) assigned."
                : 'Department updated successfully (no modules assigned).'
        );
    }

    private function ensureModulesStorageReady(): ?RedirectResponse
    {
        if (Schema::hasTable('department_modules')) {
            return null;
        }

        return back()->withInput()->withErrors([
            'module_keys' => 'The department_modules table is missing on this database. Import deploy/production.sql (or run migrations) before assigning modules.',
        ]);
    }

    private function isDescendant(int $candidateParentId, int $departmentId): bool
    {
        $currentId = $candidateParentId;

        while ($currentId) {
            if ($currentId === $departmentId) {
                return true;
            }

            $currentId = Department::query()->where('id', $currentId)->value('parent_dept_id');
        }

        return false;
    }
}
