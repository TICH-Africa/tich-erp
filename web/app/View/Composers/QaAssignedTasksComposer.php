<?php

namespace App\View\Composers;

use App\Services\Qa\QaAssessmentService;
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

        $view->with('qaPendingTasks', $this->qa->outstandingTasksForUser($user));
    }
}
