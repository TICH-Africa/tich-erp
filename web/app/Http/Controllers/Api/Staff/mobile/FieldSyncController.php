<?php

namespace App\Http\Controllers\Api\Staff\mobile;

use App\Http\Controllers\Api\Staff\mobile\Concerns\ResolvesPortalTeachingStaff;
use App\Http\Controllers\Controller;
use App\Models\AttendanceSession;
use App\Models\UnitAllocation;
use App\Services\StaffTeachingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;

class FieldSyncController extends Controller
{
    use ResolvesPortalTeachingStaff;

    public function __construct(protected StaffTeachingService $teaching) {}

    /**
     * Apply batched offline operations from mobile field staff (JSON or gzip-compressed JSON body).
     *
     * @return JsonResponse{applied: list<string>, failed: list<array{id: string, message: string}>, skipped: list<string>}
     */
    public function store(Request $request): JsonResponse
    {
        $staff = $this->portalStaff($request);
        $payload = $this->decodePayload($request);

        $validator = Validator::make($payload, [
            'operations' => ['required', 'array', 'max:40'],
            'operations.*.id' => ['required', 'string', 'max:64'],
            'operations.*.type' => ['required', 'string', 'max:80'],
            'operations.*.payload' => ['required', 'array'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Invalid sync payload.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $applied = [];
        $failed = [];
        $skipped = [];

        foreach ($validator->validated()['operations'] as $operation) {
            $opId = (string) $operation['id'];
            $cacheKey = 'mobile_field_sync:'.$staff->id.':'.$opId;

            if (Cache::has($cacheKey)) {
                $skipped[] = $opId;

                continue;
            }

            try {
                $this->applyOperation($staff, (string) $operation['type'], $operation['payload']);
                Cache::put($cacheKey, true, now()->addDays(14));
                $applied[] = $opId;
            } catch (\Throwable $e) {
                $failed[] = [
                    'id' => $opId,
                    'message' => $e instanceof \InvalidArgumentException
                        ? $e->getMessage()
                        : 'Operation could not be applied.',
                ];
            }
        }

        return response()->json([
            'message' => count($applied) > 0
                ? count($applied).' operation(s) synced.'
                : (count($failed) > 0 ? 'Some operations failed.' : 'Nothing new to sync.'),
            'applied' => $applied,
            'failed' => $failed,
            'skipped' => $skipped,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function decodePayload(Request $request): array
    {
        $raw = $request->getContent();
        if ($request->header('Content-Encoding') === 'gzip' && is_string($raw) && $raw !== '') {
            $decoded = @gzdecode($raw);
            if ($decoded !== false) {
                $raw = $decoded;
            }
        }

        if (is_string($raw) && $raw !== '') {
            $json = json_decode($raw, true);
            if (is_array($json)) {
                return $json;
            }
        }

        return $request->all();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function applyOperation(\App\Models\Staff $staff, string $type, array $payload): void
    {
        match ($type) {
            'attendance.save' => $this->applyAttendanceSave($staff, $payload),
            'grading.cat' => $this->applyGradingCat($staff, $payload),
            default => throw new \InvalidArgumentException('Unsupported operation type: '.$type),
        };
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function applyAttendanceSave(\App\Models\Staff $staff, array $payload): void
    {
        $validated = Validator::validate($payload, [
            'session_id' => ['required', 'integer'],
            'present' => ['array'],
            'present.*' => ['integer'],
            'lock' => ['sometimes', 'boolean'],
        ]);

        $session = AttendanceSession::query()->findOrFail((int) $validated['session_id']);
        $session->loadMissing('allocation');
        abort_unless(
            (int) $session->recorded_by === (int) $staff->id
            || (int) ($session->allocation?->staff_id) === (int) $staff->id,
            403
        );

        $present = $validated['present'] ?? [];
        $this->teaching->saveAttendance($session, $staff, $present);

        if (($validated['lock'] ?? false) === true) {
            $this->teaching->lockAttendanceSession($session->fresh(), $staff);
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function applyGradingCat(\App\Models\Staff $staff, array $payload): void
    {
        $validated = Validator::validate($payload, [
            'allocation_id' => ['required', 'integer'],
            'student_id' => ['required', 'integer'],
            'assessment_name' => ['required', 'string', 'max:200'],
            'assessment_type' => ['nullable', 'string', 'max:50'],
            'max_score' => ['required', 'numeric', 'min:0.01'],
            'score_obtained' => ['required', 'numeric', 'min:0'],
            'weight_in_final' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        $allocation = UnitAllocation::query()->findOrFail((int) $validated['allocation_id']);
        abort_unless((int) $allocation->staff_id === (int) $staff->id, 403);

        $this->teaching->recordCatScore($staff, $allocation, $validated);
    }
}
