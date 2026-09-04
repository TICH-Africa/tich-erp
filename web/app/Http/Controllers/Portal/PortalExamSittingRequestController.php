<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\SpecialExamRequest;
use App\Models\SupplementaryExamRequest;
use App\Services\StudentPortalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

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
            'reason' => 'required|string|max:5000',
            'supplementary_type' => 'nullable|in:theory,clinical,both',
        ]);

        if ($validated['request_type'] === 'special_exam') {
            abort_unless(Schema::hasTable('special_exam_requests'), 404);

            SpecialExamRequest::query()->create([
                'student_id' => $student->id,
                'exam_result_id' => null,
                'unit_id' => (int) $validated['unit_id'],
                'semester_id' => (int) $validated['semester_id'],
                'reason' => $validated['reason'],
                'student_notes' => $validated['reason'],
                'status' => 'pending',
                'created_at' => now(),
            ]);

            $message = 'Your special exam request was submitted for review.';
        } else {
            abort_unless(Schema::hasTable('supplementary_requests'), 404);

            SupplementaryExamRequest::query()->create([
                'student_id' => $student->id,
                'exam_result_id' => null,
                'unit_id' => (int) $validated['unit_id'],
                'semester_id' => (int) $validated['semester_id'],
                'supplementary_type' => $validated['supplementary_type'] ?? 'theory',
                'fee_amount' => 0,
                'fee_paid' => 0,
                'application_status' => 'pending_review',
                'student_notes' => $validated['reason'],
                'created_at' => now(),
            ]);

            $message = 'Your supplementary exam request was submitted for review.';
        }

        return redirect()
            ->route('portal.dashboard', ['section' => 'exam-requests'])
            ->with('success', $message);
    }
}
