<?php

namespace App\Http\Controllers\Api\Staff\mobile;

use App\Http\Controllers\Api\Staff\mobile\Concerns\ResolvesPortalTeachingStaff;
use App\Http\Controllers\Controller;
use App\Services\StaffMobilePortalSerializer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    use ResolvesPortalTeachingStaff;

    public function __construct(
        protected StaffMobilePortalSerializer $serializer,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $staff = $this->portalStaff($request);
        $cacheKey = 'staff.mobile.dashboard.'.$staff->id.'.v1';

        $payload = Cache::remember($cacheKey, 20, fn () => $this->serializer->overview($staff));

        return response()->json($payload);
    }
}
