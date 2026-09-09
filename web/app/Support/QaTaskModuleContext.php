<?php

namespace App\Support;

use App\Models\Department;
use App\Services\DepartmentBudgetingService;
use Illuminate\Http\Request;

class QaTaskModuleContext
{
    /**
     * Extra hosts (not in budgeting MODULES) that still host QA task pages.
     *
     * @var array<string, array{layout: string, content_section: string, route_prefix: string, url_prefix?: string}>
     */
    public const EXTRA_MODULES = [
        'employee' => [
            'layout' => 'layouts.employee',
            'content_section' => 'employee-content',
            'route_prefix' => 'employee.qa.tasks',
        ],
    ];

    /**
     * @return array{
     *     key: string,
     *     layout: string,
     *     content_section: string,
     *     routes: array{index: string, show: string, store: string},
     *     params: array<string, mixed>,
     *     department?: Department|null
     * }
     */
    public static function resolve(Request $request): array
    {
        $name = (string) $request->route()?->getName();
        $key = self::moduleKeyFromRouteName($name);

        return self::forModule($key, $request);
    }

    /**
     * @return array{
     *     key: string,
     *     layout: string,
     *     content_section: string,
     *     routes: array{index: string, show: string, store: string},
     *     params: array<string, mixed>,
     *     department?: Department|null
     * }
     */
    public static function forModule(string $key, ?Request $request = null): array
    {
        $request ??= request();

        if ($key === 'qa') {
            return [
                'key' => 'qa',
                'layout' => 'layouts.qa',
                'content_section' => 'qa-content',
                'routes' => self::routeNames('qa'),
                'params' => [],
                'department' => null,
            ];
        }

        if ($key === 'employee') {
            return [
                'key' => 'employee',
                'layout' => 'layouts.employee',
                'content_section' => 'employee-content',
                'routes' => self::routeNames('employee'),
                'params' => [],
                'department' => null,
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
                'params' => [],
                'department' => $hub,
            ];
        }

        $modules = DepartmentBudgetingService::MODULES;
        abort_unless(isset($modules[$key]), 404, 'Unknown QA task module.');

        $config = $modules[$key];

        return [
            'key' => $key,
            'layout' => $config['layout'],
            'content_section' => $config['content_section'],
            'routes' => self::routeNames($key),
            'params' => [],
            'department' => null,
        ];
    }

    /**
     * Best module host for a target department (notifications / deep links).
     */
    public static function moduleKeyForDepartment(Department $department): string
    {
        foreach (DepartmentBudgetingService::MODULES as $module => $config) {
            if ($department->dept_code === $config['dept_code']) {
                return $module === 'monitoring_evaluation' ? 'monitoring_evaluation' : $module;
            }
        }

        if ($department->isLearningDepartment() || $department->isAcademicsHub()) {
            return 'academics';
        }

        return 'qa';
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    public static function url(string $action, string $moduleKey, array $extra = []): string
    {
        $routes = self::routeNames($moduleKey);
        abort_unless(isset($routes[$action]), 500);

        if (in_array($moduleKey, ['academics', 'departments'], true) && $action !== 'index') {
            if (isset($extra['department']) && ! isset($extra['targetDepartment'])) {
                $extra['targetDepartment'] = $extra['department'];
            }
            unset($extra['department']);
        }

        return route($routes[$action], $extra);
    }

    /**
     * @return array{index: string, show: string, store: string}
     */
    public static function routeNames(string $moduleKey): array
    {
        if ($moduleKey === 'qa') {
            return [
                'index' => 'qa.tasks.index',
                'show' => 'qa.tasks.show',
                'store' => 'qa.tasks.store',
            ];
        }

        if ($moduleKey === 'academics' || $moduleKey === 'departments') {
            return [
                'index' => 'departments.academics.qa.tasks.index',
                'show' => 'departments.academics.qa.tasks.show',
                'store' => 'departments.academics.qa.tasks.store',
            ];
        }

        $prefix = $moduleKey.'.qa.tasks';

        return [
            'index' => $prefix.'.index',
            'show' => $prefix.'.show',
            'store' => $prefix.'.store',
        ];
    }

    /**
     * Infer current module from the active request (for dashboard panels / sidebars).
     */
    public static function currentModuleKey(?Request $request = null): string
    {
        $request ??= request();
        $name = (string) $request->route()?->getName();

        if ($name !== '') {
            return self::moduleKeyFromRouteName($name);
        }

        return 'qa';
    }

    public static function moduleKeyFromRouteName(string $name): string
    {
        if ($name === '' || str_starts_with($name, 'qa.tasks.')) {
            return 'qa';
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

        $first = explode('.', $name)[0] ?? 'qa';

        if (isset(DepartmentBudgetingService::MODULES[$first]) || isset(self::EXTRA_MODULES[$first])) {
            return $first;
        }

        return 'qa';
    }

    /**
     * Sidebar / panel helper: index URL for the module the user is currently in.
     *
     * @param  array<string, mixed>  $params
     */
    public static function indexUrlForCurrentContext(array $params = []): string
    {
        $key = self::currentModuleKey();

        return self::url('index', $key, $params);
    }

    /**
     * @param  array<string, mixed>  $params
     */
    public static function showUrlForCurrentContext($plan, $department, array $params = []): string
    {
        $key = self::currentModuleKey();

        return self::url('show', $key, array_merge($params, [
            'plan' => $plan,
            'department' => $department,
        ]));
    }

    public static function routeIsPattern(string $moduleKey): string
    {
        if ($moduleKey === 'qa') {
            return 'qa.tasks.*';
        }

        if ($moduleKey === 'academics') {
            return 'departments.academics.qa.tasks.*';
        }

        return $moduleKey.'.qa.tasks.*';
    }
}
