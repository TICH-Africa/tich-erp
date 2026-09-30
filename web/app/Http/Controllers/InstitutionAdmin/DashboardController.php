<?php

namespace App\Http\Controllers\InstitutionAdmin;

use App\Http\Controllers\Controller;
use App\Services\CeoDashboardAnalyticsService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        protected CeoDashboardAnalyticsService $analytics,
    ) {}

    public function __invoke(): View
    {
        $overview = $this->analytics->overview();

        return view('ceo.dashboard', [
            'executiveLayout' => 'layouts.institution-admin',
            'executiveContentSection' => 'institution-admin-content',
            'portalEyebrow' => 'Chief Institution Administrator',
            'portalTitle' => 'Institution oversight',
            'portalLede' => 'Read-only view of workforce, students, finance, and executive queues. You can inspect everything but cannot approve or sign.',
            'queues' => $overview['queues'],
            'finance' => $overview['finance'],
            'academics' => $overview['academics'],
            'admissions' => $overview['admissions'],
            'workforce' => $overview['workforce'],
            'procurement' => $overview['procurement'],
            'pendingBudgets' => $overview['queues']['budgets'] ?? 0,
            'pendingCurriculum' => $overview['queues']['curriculum'] ?? 0,
            'pendingProcurement' => $overview['queues']['procurement'] ?? 0,
        ]);
    }
}
