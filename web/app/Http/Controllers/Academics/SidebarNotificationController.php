<?php

namespace App\Http\Controllers\Academics;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Services\Sidebar\AcademicsSidebarNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SidebarNotificationController extends DepartmentAcademicsController
{
    public function __invoke(
        Request $request,
        Department $department,
        AcademicsSidebarNotificationService $notifications,
    ): JsonResponse {
        $hub = $this->authorizeHub($request, $department, allowSuggestionsOnly: true);

        return response()->json([
            'counts' => $notifications->countsFor($request->user(), $hub, true),
            'labels' => $notifications->formattedCountsFor($request->user(), $hub, true),
        ]);
    }
}
