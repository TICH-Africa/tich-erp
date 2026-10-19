<?php

namespace App\Http\Controllers\Api\Staff\mobile;

use App\Http\Controllers\Api\Staff\mobile\Concerns\ResolvesPortalTeachingStaff;
use App\Http\Controllers\Controller;
use App\Services\StaffMobilePortalSerializer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UnitsController extends Controller
{
    use ResolvesPortalTeachingStaff;

    public function __construct(
        protected StaffMobilePortalSerializer $serializer,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        return response()->json($this->serializer->units($this->portalStaff($request)));
    }
}
