<?php

namespace App\View\Composers;

use App\Services\Qa\QaAssessmentService;
use App\Support\QaTaskModuleContext;
use Illuminate\View\View;

class QaAssignedTasksComposer
{
    public function __construct(
        protected QaAssessmentService $qa,
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

        $moduleKey = QaTaskModuleContext::currentModuleKey();
        $scopeIds = QaTaskModuleContext::taskScopeDepartmentIds(
            QaTaskModuleContext::forModule($moduleKey)
        );

        $view->with('qaPendingTasks', $this->qa->outstandingTasksForUser($user, $scopeIds));
    }
}
