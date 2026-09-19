<?php

namespace App\Http\Controllers\Research;

use App\Http\Controllers\Controller;
use App\Models\Portal\ResearchProject;
use App\Services\Research\ResearchActivityService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        protected ResearchActivityService $activities,
    ) {}

    public function __invoke(): View
    {
        $recent = ResearchProject::query()
            ->orderByDesc('id')
            ->limit(8)
            ->get();

        foreach ($recent as $item) {
            $this->activities->syncLifecycleStatus($item);
        }

        return view('research.dashboard', [
            'stats' => $this->activities->dashboardStats(),
            'recent' => $recent,
        ]);
    }
}
