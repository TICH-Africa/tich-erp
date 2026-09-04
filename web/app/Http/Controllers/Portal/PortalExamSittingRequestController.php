<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\SpecialExamRequest;
use App\Models\SupplementaryExamRequest;
use App\Services\StudentPortalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PortalExamSittingRequestController extends Controller
{
    public function __construct(
        protected StudentPortalService $portalService,
    ) {}

    public function store(Request $request): RedirectResponse
    {
        $student = $this->portalService->studentForUser($request->user());
        abort_if(! $student, 404);

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

        $redirect = redirect()->route('portal.dashboard', [
            'section' => 'academics',
            'tab' => 'exam-requests',
        ]);

        if ($validated['request_type'] === 'special_exam') {
            abort_unless(Schema::hasTable('special_exam_requests'), 404);

            $storedPaths = [];
            foreach ($request->file('attachments', []) as $file) {
                if (! $file) {
                    continue;
                }
                $original = $file->getClientOriginalName();
                $path = $file->storeAs(
                    'student-requests/special-exam/'.$student->id,
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

            abort_if($storedPaths === [], 422, 'Supporting document(s) are required for special exam requests.');

            SpecialExamRequest::query()->create([
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

            return $redirect->with('success', 'Your special exam request was submitted for review.');
        }

        abort_unless(Schema::hasTable('supplementary_requests'), 404);

        SupplementaryExamRequest::query()->create([
            'student_id' => $student->id,
            'exam_result_id' => null,
            'unit_id' => (int) $validated['unit_id'],
            'semester_id' => (int) $validated['semester_id'],
            'supplementary_type' => 'theory',
            'fee_amount' => 0,
            'fee_paid' => 0,
            'application_status' => 'pending_review',
            'student_notes' => null,
            'created_at' => now(),
        ]);

        return $redirect->with('success', 'Your supplementary exam request was submitted for review.');
    }

    public function downloadSpecialAttachment(Request $request, SpecialExamRequest $specialExamRequest, int $index)
    {
        $student = $this->portalService->studentForUser($request->user());
        abort_if(! $student || (int) $specialExamRequest->student_id !== (int) $student->id, 404);

        $attachments = $specialExamRequest->supporting_docs ?? [];
        abort_unless(isset($attachments[$index]['path']), 404);
        $path = $attachments[$index]['path'];
        abort_unless(Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->download(
            $path,
            $attachments[$index]['original_name'] ?? basename($path)
        );
    }
}
