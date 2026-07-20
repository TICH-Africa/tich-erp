<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Campus;
use App\Models\Department;
use App\Models\Role;
use App\Models\User;
use App\Services\DashboardService;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    public function index(DashboardService $dashboardService): View
    {
        return view('admin.index', [
            'stats' => [
                'campuses' => Campus::query()->where('is_active', 1)->count(),
                'departments' => Department::query()->where('is_active', 1)->count(),
                'users' => User::query()->where('is_active', 1)->count(),
                'roles' => Role::query()->count(),
            ],
            'modules' => $dashboardService->allModules(),
        ]);
    }
}
