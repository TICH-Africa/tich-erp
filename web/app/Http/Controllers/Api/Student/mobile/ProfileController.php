<?php

namespace App\Http\Controllers\Api\Student\mobile;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Student\mobile\Concerns\ResolvesPortalStudent;
use App\Http\Controllers\Api\Student\mobile\Concerns\SerializesStudentPortal;
use App\Models\StudentProfileChangeRequest;
use App\Services\StudentProfileChangeService;
use App\Services\StudentRecordService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;

class ProfileController extends Controller
{
    use ResolvesPortalStudent;
    use SerializesStudentPortal;

    public function __construct(
        protected StudentRecordService $studentRecords,
        protected StudentProfileChangeService $profileChanges,
    ) {}

    public function show(Request $request): JsonResponse
    {
        $student = $this->portalStudent($request);
        $biodata = $this->studentRecords->biodata360($student);

        $changeRequests = [];
        if (Schema::hasTable('student_profile_change_requests')) {
            $changeRequests = StudentProfileChangeRequest::query()
                ->where('student_id', $student->id)
                ->orderByDesc('created_at')
                ->limit(20)
                ->get()
                ->map(fn ($r) => [
                    'id' => $r->id,
                    'status' => $r->status,
                    'requested_fields' => $r->requested_fields,
                    'student_notes' => $r->student_notes ?? null,
                    'created_at' => optional($r->created_at)?->toIso8601String(),
                    'reviewed_at' => optional($r->reviewed_at)?->toIso8601String(),
                ])
                ->all();
        }

        return response()->json([
            'student' => $this->serializeStudent($student, $biodata),
            'biodata' => $biodata,
            'change_requests' => $changeRequests,
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $student = $this->portalStudent($request);

        $validated = $request->validate([
            'phone_number' => 'nullable|string|max:40',
            'email' => 'nullable|email|max:191',
            'home_county' => 'nullable|string|max:120',
            'nationality' => 'nullable|string|max:100',
            'postal_address' => 'nullable|string|max:255',
            'next_of_kin_name' => 'nullable|string|max:191',
            'next_of_kin_relationship' => 'nullable|string|max:80',
            'next_of_kin_phone' => 'nullable|string|max:40',
            'next_of_kin_address' => 'nullable|string|max:255',
            'emergency_contact_name' => 'nullable|string|max:191',
            'emergency_contact_phone' => 'nullable|string|max:40',
            'emergency_contact_relationship' => 'nullable|string|max:80',
            'first_name' => 'nullable|string|max:100',
            'middle_name' => 'nullable|string|max:100',
            'surname' => 'nullable|string|max:100',
            'date_of_birth' => 'nullable|date',
            'national_id_number' => 'nullable|string|max:40',
            'passport_number' => 'nullable|string|max:40',
            'student_notes' => 'nullable|string|max:2000',
        ]);

        try {
            $result = $this->profileChanges->submit($student, $request->user(), $validated);
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $parts = [];
        if ($result['applied'] !== []) {
            $parts[] = 'Contact details updated.';
        }
        if ($result['queued']) {
            $parts[] = 'Name / ID / date-of-birth changes were submitted for registrar approval.';
        }
        if ($result['photo_queued'] ?? false) {
            $parts[] = 'Photo change was submitted for registrar approval.';
        }

        return response()->json([
            'message' => implode(' ', $parts) ?: 'Profile updated.',
            'result' => $result,
        ]);
    }
}
