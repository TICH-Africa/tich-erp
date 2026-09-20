<?php

namespace App\Http\Controllers\Research;

use App\Http\Controllers\Controller;
use App\Models\Portal\PartnershipRequest;
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

        $partnerships = PartnershipRequest::query()
            ->orderByDesc('id')
            ->limit(8)
            ->get();

        return view('research.dashboard', [
            'stats' => $this->activities->dashboardStats(),
            'recent' => $recent,
            'partnerships' => $partnerships,
            'partnershipPending' => PartnershipRequest::query()->where('status', 'pending_review')->count(),
        ]);
    }
}
