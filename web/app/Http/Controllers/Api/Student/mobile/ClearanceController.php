<?php

namespace App\Http\Controllers\Api\Student\mobile;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Student\mobile\Concerns\ResolvesPortalStudent;
use App\Http\Controllers\Api\Student\mobile\Concerns\SerializesStudentPortal;
use App\Services\StudentClearanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClearanceController extends Controller
{
    use ResolvesPortalStudent;
    use SerializesStudentPortal;

    public function __construct(
        protected StudentClearanceService $clearance,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $student = $this->portalStudent($request);
        $items = $this->clearance->ensureDefaults($student);

        return response()->json([
            'student' => $this->serializeStudent($student),
            'items' => collect($items)->map(fn ($item) => [
                'id' => $item->id,
                'department_key' => $item->department_key,
                'department_label' => $item->label
                    ?? ucwords(str_replace('_', ' ', (string) ($item->department_key ?? 'item'))),
                'status' => $item->status,
                'notes' => $item->notes ?? $item->reviewer_notes ?? null,
                'cleared_at' => optional($item->cleared_at ?? null)?->toIso8601String(),
                'updated_at' => optional($item->updated_at ?? null)?->toIso8601String(),
            ])->values()->all(),
        ]);
    }

    public function ensure(Request $request): JsonResponse
    {
        $student = $this->portalStudent($request);
        $this->clearance->ensureDefaults($student);

        return response()->json(['message' => 'Clearance items refreshed.']);
    }
}
