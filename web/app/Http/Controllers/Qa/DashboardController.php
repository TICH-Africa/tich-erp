<?php

namespace App\Http\Controllers\Qa;

use App\Http\Controllers\Controller;
use App\Models\Qa\QaCorrectiveAction;
use App\Models\Qa\QaPlan;
use App\Services\Qa\QaAssessmentService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(QaAssessmentService $qa): View
    {
        $openPlans = QaPlan::query()
            ->whereIn('status', ['draft', 'dispatched', 'in_progress'])
            ->orderByDesc('id')
            ->limit(8)
            ->get();

        $openActions = QaCorrectiveAction::query()
            ->with(['department', 'plan'])
            ->whereIn('status', ['open', 'in_progress', 'overdue'])
            ->orderBy('resolution_deadline')
            ->limit(8)
            ->get();

        $qaPendingTasks = $qa->outstandingTasksForUser(auth()->user());

        return view('qa.dashboard', [
            'openPlans' => $openPlans,
            'openActions' => $openActions,
            'pendingTasks' => $qaPendingTasks->count(),
            'qaPendingTasks' => $qaPendingTasks,
            'stats' => [
                'draft' => QaPlan::query()->where('status', 'draft')->count(),
                'active' => QaPlan::query()->whereIn('status', ['dispatched', 'in_progress'])->count(),
                'compiled' => QaPlan::query()->where('status', 'compiled')->count(),
                'corrective' => QaCorrectiveAction::query()->whereIn('status', ['open', 'in_progress', 'overdue'])->count(),
            ],
        ]);
    }
}
