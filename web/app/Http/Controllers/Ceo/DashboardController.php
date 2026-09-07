<?php

namespace App\Http\Controllers\Ceo;

use App\Http\Controllers\Controller;
use App\Models\AcademicProgram;
use App\Models\Administration\BudgetRequest;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $pendingBudgets = 0;
        $pendingCurriculum = 0;

        if (Schema::hasTable('admin_budget_requests')) {
            $pendingBudgets = BudgetRequest::query()
                ->where('status', 'executive_review')
                ->count();
        }

        if (Schema::hasTable('academic_programs')) {
            $pendingCurriculum = AcademicProgram::query()
                ->where('status', 'pending_ceo')
                ->count();
        }

        return view('ceo.dashboard', [
            'pendingBudgets' => $pendingBudgets,
            'pendingCurriculum' => $pendingCurriculum,
        ]);
    }
}
