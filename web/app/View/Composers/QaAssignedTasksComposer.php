<?php

namespace App\View\Composers;

use App\Support\QaTaskModuleContext;
use App\Services\Qa\QaAssessmentService;
use App\Services\DepartmentBudgetingService;
use App\Models\Department;
use Illuminate\View\View;
use Illuminate\Http\Request;

class QaAssignedTasksComposer
{
    public function __construct(
        protected QaAssessmentService $qa,
        protected Request $request,
    ) {}

    public function compose(View $view): void
    {
        if ($view->offsetExists('qaPendingTasks')) {
            return;
        }

        $user = auth()->user();
        if (! $user) {
            $view->with('qaPendingTasks', collect());

            return;
        }

        $moduleContext = QaTaskModuleContext::resolve($this->request);
        $limitDepartmentIds = $this->limitDepartmentIds($moduleContext);

        $view->with('qaPendingTasks', $this->qa->outstandingTasksForUser($user, $limitDepartmentIds));
    }

    private function limitDepartmentIds(array $moduleContext): ?array
    {
        $key = $moduleContext['key'] ?? 'qa';

        if ($key === 'qa') {
            return null;
        }

        if ($key === 'academics') {
            $target = $this->safeRoute('targetDepartment');
            return $target instanceof Department ? [(int) $target->id] : null;
        }

        $department = $this->safeRoute('department');
        if ($department instanceof Department) {
            return [(int) $department->id];
        }

        $moduleKey = $moduleContext['key'] ?? $key;
        if (isset(DepartmentBudgetingService::MODULES[$moduleKey])) {
            $deptCode = DepartmentBudgetingService::MODULES[$moduleKey]['dept_code'];
            $dept = Department::where('dept_code', $deptCode)->first();
            return $dept ? [(int) $dept->id] : null;
        }

        return null;
    }

    private function safeRoute(string $parameter)
    {
        try {
            return $this->request->route($parameter);
        } catch (\Throwable) {
            return null;
        }
    }
}