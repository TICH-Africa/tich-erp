<?php

namespace App\Http\Controllers\Qa;

use App\Http\Controllers\Controller;
use App\Models\Qa\QcaFlag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QcaDownstreamLockController extends Controller
{
    public function check(Request $request): JsonResponse
    {
        $module = $request->get('module');
        $entityType = $request->get('entity_type');
        $entityId = $request->get('entity_id');

        if (! $module || ! $entityType || ! $entityId) {
            return response()->json(['locked' => false], 422);
        }

        $activeFlags = QcaFlag::query()
            ->whereIn('status', ['open', 'in_progress'])
            ->whereIn('severity', ['High', 'Critical'])
            ->get();

        $blockingFlags = [];
        foreach ($activeFlags as $flag) {
            if (! $flag->isModuleLocked($module)) {
                continue;
            }

            $blockingFlags[] = [
                'id' => $flag->id,
                'flag_number' => $flag->flag_number,
                'category' => $flag->category,
                'severity' => $flag->severity,
                'description' => $flag->description,
                'assigned_to' => $flag->assignedTo?->first_name.' '.$flag->assignedTo?->surname,
                'deadline' => $flag->resolution_deadline?->toDateString(),
            ];
        }

        return response()->json([
            'locked' => count($blockingFlags) > 0,
            'blocking_flags' => $blockingFlags,
        ]);
    }
}
