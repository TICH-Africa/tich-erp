<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(DashboardService $dashboardService): View
    {
        $user = auth()->user();

        return view('dashboard', [
            'modules' => $dashboardService->modulesForUser($user),
            'isSuperAdmin' => $user->hasRole('Super Admin'),
        ]);
    }
}
