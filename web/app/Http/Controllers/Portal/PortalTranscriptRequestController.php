<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\StudentTranscriptRequest;
use App\Services\StudentPortalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class PortalTranscriptRequestController extends Controller
{
    public function __construct(
        protected StudentPortalService $portalService,
    ) {}

    public function store(Request $request): RedirectResponse
    {
        $student = $this->portalService->studentForUser($request->user());
        abort_if(! $student, 404);

        $validated = $request->validate([
            'delivery_method' => 'required|in:download,email,collect',
            'student_notes' => 'nullable|string|max:2000',
        ]);

        $open = StudentTranscriptRequest::query()
            ->where('student_id', $student->id)
            ->whereIn('status', ['pending', 'processing'])
            ->exists();

        if ($open) {
            return redirect()
                ->route('portal.dashboard', ['section' => 'academics', 'tab' => 'exams'])
                ->withErrors(['transcript' => 'You already have an open transcript request.']);
        }

        StudentTranscriptRequest::query()->create([
            'student_id' => $student->id,
            'requested_by_user_id' => $request->user()->id,
            'status' => 'pending',
            'delivery_method' => $validated['delivery_method'],
            'student_notes' => $validated['student_notes'] ?? null,
        ]);

        return redirect()
            ->route('portal.dashboard', ['section' => 'academics', 'tab' => 'exams'])
            ->with('success', 'Official transcript request submitted. Unofficial grades remain viewable on this page.');
    }
}
