<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\StudentSuggestion;
use App\Services\StudentPortalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PortalSuggestionController extends Controller
{
    public function __construct(
        protected StudentPortalService $portalService,
    ) {}

    public function store(Request $request): RedirectResponse
    {
        $student = $this->portalService->studentForUser($request->user());
        abort_if(! $student, 404);

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

        $message = $request->boolean('is_anonymous')
            ? 'Your anonymous submission was sent to Academics. Your name and registration number will not be shared.'
            : 'Your submission was sent to Academics. Thank you.';

        return redirect()
            ->route('portal.dashboard', ['section' => 'suggestions'])
            ->with('success', $message);
    }
}
