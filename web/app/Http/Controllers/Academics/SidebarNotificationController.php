<?php

namespace App\Http\Controllers\Academics;

use App\Models\Department;
use App\Services\Sidebar\AcademicsSidebarNotificationService;
use App\Support\QaTaskSidebarBadge;
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
        $counts = $notifications->countsFor($request->user(), $hub, true);
        $labels = $notifications->formattedCountsFor($request->user(), $hub, true);
        [$counts, $labels] = QaTaskSidebarBadge::merge($counts, $labels, $request->user());

        return response()->json([
            'counts' => $counts,
            'labels' => $labels,
        ]);
    }
}
