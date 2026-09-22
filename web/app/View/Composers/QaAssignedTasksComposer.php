<?php

namespace App\View\Composers;

use Illuminate\View\View;

/**
 * Department QA assessment tasks removed — IQA is QA-module only.
 */
class QaAssignedTasksComposer
{
    public function compose(View $view): void
    {
        if (! $view->offsetExists('qaPendingTasks')) {
            $view->with('qaPendingTasks', collect());
        }
    }
}
