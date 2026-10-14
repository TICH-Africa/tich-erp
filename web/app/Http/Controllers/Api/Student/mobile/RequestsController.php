<?php

namespace App\Http\Controllers\Api\Student\mobile;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Student\mobile\Concerns\ResolvesPortalStudent;
use App\Models\SpecialExamRequest;
use App\Models\StudentLifecycleRequest;
use App\Models\StudentSuggestion;
use App\Models\StudentTranscriptRequest;
use App\Models\SupplementaryExamRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class RequestsController extends Controller
{
    use ResolvesPortalStudent;

    public function lifecycleIndex(Request $request): JsonResponse
    {
        $student = $this->portalStudent($request);
        if (! Schema::hasTable('student_lifecycle_requests')) {
            return response()->json(['requests' => []]);
        }

        $requests = StudentLifecycleRequest::query()
            ->where('student_id', $student->id)
            ->orderByDesc('created_at')
            ->limit(30)
            ->get()
            ->map(fn ($r) => [
                'id' => $r->id,
                'request_type' => $r->request_type,
                'status' => $r->status,
                'registrar_status' => $r->registrar_status,
                'dean_status' => $r->dean_status,
                'deferment_months' => $r->deferment_months,
                'reason' => $r->reason,
                'created_at' => optional($r->created_at)?->toIso8601String(),
            ]);

        return response()->json(['requests' => $requests]);
    }

    public function storeLifecycle(Request $request): JsonResponse
    {
        $student = $this->portalStudent($request);

        $validated = $request->validate([
            'deferment_months' => 'required|integer|min:1|max:36',
            'reason' => 'required|string|max:5000',
            'attachments' => 'required|array|min:1|max:5',
            'attachments.*' => 'file|max:10240|mimes:pdf,jpg,jpeg,png,doc,docx',
        ]);

        $storedPaths = [];
        foreach ($request->file('attachments', []) as $file) {
            if (! $file) {
                continue;
            }
            $original = $file->getClientOriginalName();
            $path = $file->storeAs(
                'student-requests/deferment/'.$student->id,
                Str::uuid()->toString().'_'.Str::slug(pathinfo($original, PATHINFO_FILENAME)).'.'.$file->getClientOriginalExtension(),
                'local'
            );
            $storedPaths[] = [
                'path' => $path,
                'original_name' => $original,
                'mime' => $file->getClientMimeType(),
                'size' => $file->getSize(),
            ];
        }

        abort_if($storedPaths === [], 422, 'At least one supporting document is required.');

        $row = StudentLifecycleRequest::query()->create([
            'student_id' => $student->id,
            'requested_by_user_id' => $request->user()->id,
            'request_type' => 'deferment',
            'status' => 'pending',
            'registrar_status' => 'pending',
            'dean_status' => 'pending',
            'deferment_months' => (int) $validated['deferment_months'],
            'reason' => $validated['reason'],
            'attachments' => $storedPaths,
        ]);

        return response()->json([
            'message' => 'Your deferment request was submitted for review.',
            'request' => ['id' => $row->id, 'status' => $row->status],
        ], 201);
    }

    public function storeSuggestion(Request $request): JsonResponse
    {
        $student = $this->portalStudent($request);

        $validated = $request->validate([
            'category' => 'required|in:suggestion,comment,complaint',
            'subject' => 'nullable|string|max:200',
            'body' => 'required|string|max:5000',
            'is_anonymous' => 'nullable|boolean',
        ]);

        StudentSuggestion::query()->create([
            'student_id' => $student->id,
            'is_anonymous' => $request->boolean('is_anonymous'),
            'category' => $validated['category'],
            'subject' => $validated['subject'] ?? null,
            'body' => $validated['body'],
            'status' => 'open',
        ]);

        return response()->json([
            'message' => $request->boolean('is_anonymous')
                ? 'Your anonymous submission was sent to Academics.'
                : 'Your submission was sent to Academics. Thank you.',
        ], 201);
    }

    public function storeTranscript(Request $request): JsonResponse
    {
        $student = $this->portalStudent($request);

        $validated = $request->validate([
            'delivery_method' => 'required|in:download,email,collect',
            'student_notes' => 'nullable|string|max:2000',
        ]);

        $open = StudentTranscriptRequest::query()
            ->where('student_id', $student->id)
            ->whereIn('status', ['pending', 'processing'])
            ->exists();

        if ($open) {
            return response()->json([
                'message' => 'You already have an open transcript request.',
            ], 422);
        }

        $row = StudentTranscriptRequest::query()->create([
            'student_id' => $student->id,
            'requested_by_user_id' => $request->user()->id,
            'status' => 'pending',
            'delivery_method' => $validated['delivery_method'],
            'student_notes' => $validated['student_notes'] ?? null,
        ]);

        return response()->json([
            'message' => 'Official transcript request submitted.',
            'request' => ['id' => $row->id, 'status' => $row->status],
        ], 201);
    }

    public function storeExamSitting(Request $request): JsonResponse
    {
        $student = $this->portalStudent($request);

        $validated = $request->validate([
            'request_type' => 'required|in:special_exam,supplementary',
            'unit_id' => 'required|integer|exists:units,id',
            'semester_id' => 'required|integer|exists:semesters,id',
            'reason' => [
                Rule::requiredIf(fn () => $request->input('request_type') === 'special_exam'),
                'nullable',
                'string',
                'max:5000',
            ],
            'attachments' => [
                Rule::requiredIf(fn () => $request->input('request_type') === 'special_exam'),
                'nullable',
                'array',
                'max:5',
            ],
            'attachments.*' => 'file|max:10240|mimes:pdf,jpg,jpeg,png,doc,docx',
        ]);

        $storedPaths = [];
        foreach ($request->file('attachments', []) as $file) {
            if (! $file) {
                continue;
            }
            $folder = $validated['request_type'] === 'special_exam'
                ? 'student-requests/special-exam/'.$student->id
                : 'student-requests/supplementary/'.$student->id;
            $original = $file->getClientOriginalName();
            $path = $file->storeAs(
                $folder,
                Str::uuid()->toString().'_'.Str::slug(pathinfo($original, PATHINFO_FILENAME)).'.'.$file->getClientOriginalExtension(),
                'local'
            );
            $storedPaths[] = [
                'path' => $path,
                'original_name' => $original,
                'mime' => $file->getClientMimeType(),
                'size' => $file->getSize(),
            ];
        }

        if ($validated['request_type'] === 'special_exam') {
            abort_unless(Schema::hasTable('special_exam_requests'), 404);
            abort_if($storedPaths === [], 422, 'Supporting document(s) are required for special exam requests.');

            $row = SpecialExamRequest::query()->create([
                'student_id' => $student->id,
                'exam_result_id' => null,
                'unit_id' => (int) $validated['unit_id'],
                'semester_id' => (int) $validated['semester_id'],
                'reason' => $validated['reason'],
                'student_notes' => $validated['reason'],
                'supporting_docs' => $storedPaths,
                'status' => 'pending',
                'created_at' => now(),
            ]);

            return response()->json([
                'message' => 'Your special exam request was submitted for review.',
                'request' => ['id' => $row->id, 'status' => $row->status],
            ], 201);
        }

        abort_unless(Schema::hasTable('supplementary_requests'), 404);

        $row = SupplementaryExamRequest::query()->create([
            'student_id' => $student->id,
            'exam_result_id' => null,
            'unit_id' => (int) $validated['unit_id'],
            'semester_id' => (int) $validated['semester_id'],
            'supplementary_type' => 'theory',
            'fee_amount' => 0,
            'fee_paid' => 0,
            'application_status' => 'pending_review',
            'student_notes' => $validated['reason'] ?? null,
            'supporting_docs' => $storedPaths !== [] ? $storedPaths : null,
            'created_at' => now(),
        ]);

        return response()->json([
            'message' => 'Your supplementary exam request was submitted for review.',
            'request' => ['id' => $row->id, 'status' => $row->application_status],
        ], 201);
    }
}
