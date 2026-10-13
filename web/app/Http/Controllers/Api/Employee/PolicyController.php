<?php

namespace App\Http\Controllers\Api\Employee;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Employee\Concerns\ResolvesPortalStaff;
use App\Models\PolicyAcknowledgement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PolicyController extends Controller
{
    use ResolvesPortalStaff;

    public function index(Request $request): JsonResponse
    {
        $staff = $this->portalStaff($request);

        $acknowledgements = PolicyAcknowledgement::query()
            ->where('staff_id', $staff->id)
            ->with('policy')
            ->orderByDesc('id')
            ->get();

        return response()->json([
            'policies' => $acknowledgements->map(fn (PolicyAcknowledgement $a) => [
                'id' => $a->policy_id,
                'acknowledgement_id' => $a->id,
                'title' => $a->policy?->title ?? $a->policy_name,
                'description' => $a->policy?->description,
                'category' => $a->policy?->category,
                'version' => $a->policy_version ?? $a->policy?->version,
                'effective_date' => optional($a->effective_date ?? $a->policy?->effective_date)?->format('Y-m-d'),
                'acknowledged' => (bool) $a->is_acknowledged,
                'acknowledged_at' => optional($a->acknowledged_at)?->toIso8601String(),
            ])->values(),
        ]);
    }

    public function acknowledge(Request $request, int $id): JsonResponse
    {
        $staff = $this->portalStaff($request);
        $validated = $request->validate([
            'full_name' => 'required|string|max:200',
            'employee_number' => 'required|string|max:50',
            'signature' => 'nullable|string|max:2000',
            'read_confirmed' => 'required|accepted',
        ]);

        DB::transaction(function () use ($staff, $id, $validated, $request): void {
            $acknowledgement = PolicyAcknowledgement::query()
                ->where('policy_id', $id)
                ->where('staff_id', $staff->id)
                ->lockForUpdate()
                ->firstOrFail();

            abort_if($acknowledgement->is_acknowledged, 403, 'Already acknowledged.');

            $acknowledgement->update([
                'is_acknowledged' => true,
                'acknowledged_at' => now(),
                'acknowledgement_method' => 'mobile',
                'acknowledged_by' => $request->user()->id,
                'employee_number' => $validated['employee_number'],
                'signature' => $validated['signature'] ?? $validated['full_name'],
            ]);
        });

        return response()->json(['message' => 'Policy acknowledged.']);
    }
}
