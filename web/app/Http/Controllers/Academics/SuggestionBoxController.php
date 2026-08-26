<?php

namespace App\Http\Controllers\Academics;

use App\Models\Department;
use App\Models\StudentSuggestion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class SuggestionBoxController extends DepartmentAcademicsController
{
    public function index(Request $request, Department $department): View
    {
        $hub = $this->authorizeHub($request, $department);
        $search = trim((string) $request->query('search', ''));
        $status = (string) $request->query('status', '');
        $category = (string) $request->query('category', '');

        $suggestions = Schema::hasTable('student_suggestions')
            ? StudentSuggestion::query()
                ->with(['student.applicant', 'student.program:id,program_code,program_name', 'reviewer'])
                ->when($search !== '', function ($q) use ($search) {
                    $q->where(function ($inner) use ($search) {
                        $inner->where('subject', 'like', "%{$search}%")
                            ->orWhere('body', 'like', "%{$search}%")
                            ->orWhereHas('student', function ($sq) use ($search) {
                                $sq->where('registration_number', 'like', "%{$search}%")
                                    ->orWhereHas('applicant', function ($aq) use ($search) {
                                        $aq->where('first_name', 'like', "%{$search}%")
                                            ->orWhere('surname', 'like', "%{$search}%");
                                    });
                            });
                    });
                })
                ->when($status !== '' && array_key_exists($status, StudentSuggestion::STATUSES), fn ($q) => $q->where('status', $status))
                ->when($category !== '' && array_key_exists($category, StudentSuggestion::CATEGORIES), fn ($q) => $q->where('category', $category))
                ->orderByDesc('created_at')
                ->paginate(20)
                ->withQueryString()
            : collect();

        $openCount = Schema::hasTable('student_suggestions')
            ? StudentSuggestion::query()->whereIn('status', ['open', 'under_review'])->count()
            : 0;
        $resolvedCount = Schema::hasTable('student_suggestions')
            ? StudentSuggestion::query()->where('status', 'resolved')->count()
            : 0;

        return view('academics.suggestions.index', [
            'department' => $hub,
            'suggestions' => $suggestions,
            'search' => $search,
            'status' => $status,
            'category' => $category,
            'openCount' => $openCount,
            'resolvedCount' => $resolvedCount,
            'categories' => StudentSuggestion::CATEGORIES,
            'statuses' => StudentSuggestion::STATUSES,
        ]);
    }

    public function show(Request $request, Department $department, StudentSuggestion $suggestion): View
    {
        $hub = $this->authorizeHub($request, $department);
        $suggestion->load(['student.applicant', 'student.program', 'reviewer']);

        return view('academics.suggestions.show', [
            'department' => $hub,
            'suggestion' => $suggestion,
            'statuses' => StudentSuggestion::STATUSES,
        ]);
    }

    public function update(Request $request, Department $department, StudentSuggestion $suggestion): RedirectResponse
    {
        $hub = $this->authorizeHub($request, $department);

        $validated = $request->validate([
            'status' => 'required|in:open,under_review,resolved,closed',
            'response' => 'nullable|string|max:5000',
        ]);

        $payload = [
            'status' => $validated['status'],
            'response' => $validated['response'] ?? null,
            'reviewed_by' => $request->user()->id,
        ];

        if (in_array($validated['status'], ['resolved', 'closed'], true)) {
            $payload['resolved_at'] = $suggestion->resolved_at ?? now();
        } else {
            $payload['resolved_at'] = null;
        }

        $suggestion->update($payload);

        return redirect()
            ->route('departments.academics.suggestions.show', array_merge(
                \App\Support\AcademicsRouteParams::fromRequest($request),
                ['suggestion' => $suggestion->id]
            ))
            ->with('success', 'Suggestion updated.');
    }
}
