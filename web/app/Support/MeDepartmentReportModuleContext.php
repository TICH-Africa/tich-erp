<?php

namespace App\Support;

use App\Models\Department;
use App\Models\Staff;
use App\Services\DepartmentBudgetingService;
use Illuminate\Http\Request;

/**
 * Module host context for department M&E quarterly report filling.
 */
class MeDepartmentReportModuleContext
{
    public const EXTRA_MODULES = [
        'employee' => [
            'layout' => 'layouts.employee',
            'content_section' => 'employee-content',
        ],
    ];

    /**
     * @return array{
     *     key: string,
     *     layout: string,
     *     content_section: string,
     *     routes: array{index: string, open: string, edit: string, update: string, submit: string},
     *     department?: Department|null
     * }
     */
    public static function resolve(Request $request): array
    {
        return self::forModule(self::moduleKeyFromRouteName((string) $request->route()?->getName()), $request);
    }

    /**
     * @return array{
     *     key: string,
     *     layout: string,
     *     content_section: string,
     *     routes: array{index: string, open: string, edit: string, update: string, submit: string},
     *     department?: Department|null
     * }
     */
    public static function forModule(string $key, ?Request $request = null): array
    {
        $request ??= request();

        if ($key === 'employee') {
            $staffDepartment = null;
            $user = $request?->user();
            if ($user?->staff_id) {
                $deptId = Staff::query()->whereKey($user->staff_id)->value('department_id');
                if ($deptId) {
                    $staffDepartment = Department::query()->find($deptId);
                }
            }

            return [
                'key' => 'employee',
                'layout' => 'layouts.employee',
                'content_section' => 'employee-content',
                'routes' => self::routeNames('employee'),
                'department' => $staffDepartment,
            ];
        }

        if ($key === 'academics' || $key === 'departments') {
            $hub = $request?->route('department');
            if (! $hub instanceof Department) {
                $hub = Department::findAcademicsHub();
            }

            return [
                'key' => 'academics',
                'layout' => 'layouts.academics',
                'content_section' => 'academics-content',
                'routes' => self::routeNames('academics'),
                'department' => $hub,
            ];
        }

        $modules = DepartmentBudgetingService::MODULES;
        abort_unless(isset($modules[$key]), 404, 'Unknown M&E reports module.');

        $config = $modules[$key];
        $department = null;
        try {
            $department = app(DepartmentBudgetingService::class)->departmentForModule($key);
        } catch (\Throwable) {
            $department = null;
        }

        return [
            'key' => $key,
            'layout' => $config['layout'],
            'content_section' => $config['content_section'],
            'routes' => self::routeNames($key),
            'department' => $department,
        ];
    }

    /**
     * @param  array{key: string, department?: Department|null}  $moduleContext
     * @return list<int>
     */
    public static function scopeDepartmentIds(array $moduleContext, ?Request $request = null): array
    {
        $request ??= request();
        $key = (string) ($moduleContext['key'] ?? '');

        if ($key === 'academics' || $key === 'departments') {
            $hub = $moduleContext['department'] ?? Department::findAcademicsHub();
            if (! $hub) {
                return [];
            }

            $ids = [(int) $hub->id];
            $learningIds = Department::query()
                ->learningDepartments()
                ->where('parent_dept_id', $hub->id)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();

            return array_values(array_unique(array_merge($ids, $learningIds)));
        }

        $department = $moduleContext['department'] ?? null;
        if ($department instanceof Department) {
            return [(int) $department->id];
        }

        if (isset(DepartmentBudgetingService::MODULES[$key])) {
            try {
                return [(int) app(DepartmentBudgetingService::class)->departmentForModule($key)->id];
            } catch (\Throwable) {
                return [];
            }
        }

        $user = $request->user();
        if ($user?->staff_id) {
            $deptId = Staff::query()->whereKey($user->staff_id)->value('department_id');
            if ($deptId) {
                return [(int) $deptId];
            }
        }

        return [];
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    public static function url(string $action, string $moduleKey, array $extra = []): string
    {
        $routes = self::routeNames($moduleKey);
        abort_unless(isset($routes[$action]), 500);

        if (in_array($moduleKey, ['academics', 'departments'], true) && $action !== 'index') {
            // Academics hub is already in the URL prefix; no extra department param needed for report routes.
        }

        return route($routes[$action], $extra);
    }

    /**
     * @return array{index: string, open: string, edit: string, update: string, submit: string}
     */
    public static function routeNames(string $moduleKey): array
    {
        if ($moduleKey === 'academics' || $moduleKey === 'departments') {
            return [
                'index' => 'departments.academics.me-reports.index',
                'open' => 'departments.academics.me-reports.open',
                'edit' => 'departments.academics.me-reports.edit',
                'update' => 'departments.academics.me-reports.update',
                'submit' => 'departments.academics.me-reports.submit',
            ];
        }

        $prefix = $moduleKey.'.me-reports';

        return [
            'index' => $prefix.'.index',
            'open' => $prefix.'.open',
            'edit' => $prefix.'.edit',
            'update' => $prefix.'.update',
            'submit' => $prefix.'.submit',
        ];
    }

    /**
     * Best module host for a target department (deep links / open-quarter).
     */
    public static function moduleKeyForDepartment(Department $department): string
    {
        foreach (DepartmentBudgetingService::MODULES as $module => $config) {
            $codes = app(DepartmentBudgetingService::class)->departmentCodesForModule($module, $config['dept_code']);
            if (in_array($department->dept_code, $codes, true)) {
                return $module;
            }
        }

        if ($department->isLearningDepartment() || $department->isAcademicsHub()) {
            return 'academics';
        }

        return 'monitoring_evaluation';
    }

    public static function moduleKeyFromRouteName(string $name): string
    {
        if ($name === '') {
            return 'monitoring_evaluation';
        }

        if (str_starts_with($name, 'departments.academics.') || str_starts_with($name, 'academics.')) {
            return 'academics';
        }

        if (str_starts_with($name, 'employee.')) {
            return 'employee';
        }

        if (str_starts_with($name, 'monitoring_evaluation.')) {
            return 'monitoring_evaluation';
        }

        $first = explode('.', $name)[0] ?? 'monitoring_evaluation';

        if (isset(DepartmentBudgetingService::MODULES[$first]) || isset(self::EXTRA_MODULES[$first])) {
            return $first;
        }

        return 'monitoring_evaluation';
    }
}
