<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\FinancialAidOpportunity;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FinancialAidController extends Controller
{
    /**
     * The website carries both rounds that can still be applied for and the ones the school has
     * already run, because a sponsor or an applicant who missed a round needs to see that it
     * existed. A draft or archived record never reaches either list.
     */
    public function index(Request $request): View
    {
        $listing = $request->query('listing') === 'completed' ? 'completed' : 'active';
        $types = ['scholarship', 'grant', 'loan', 'work_study'];
        $type = in_array($request->query('type'), $types, true) ? $request->query('type') : null;

        $scoped = function (callable $scope) use ($type) {
            $query = FinancialAidOpportunity::query()->whereIn('status', ['published', 'closed']);
            $scope($query);
            $query->when($type, fn ($q) => $q->where('funding_type', $type));

            return $query;
        };

        // A round whose window has not opened yet still belongs under Active - the card says when
        // it opens - so only the deadline decides whether it is finished.
        $active = fn ($query) => $query
            ->where('status', 'published')
            ->where(function ($q) {
                $q->whereNull('application_deadline')->orWhere('application_deadline', '>=', now());
            });

        $completed = fn ($query) => $query->where(function ($q) {
            $q->where('status', 'closed')
              ->orWhere(function ($d) {
                  $d->whereNotNull('application_deadline')->where('application_deadline', '<', now());
              });
        });

        $opportunities = $scoped($listing === 'active' ? $active : $completed)
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        return view('pages.financial-aid.index', [
            'opportunities' => $opportunities,
            'listing' => $listing,
            'type' => $type,
            'typeOptions' => $types,
            'counts' => [
                'active' => $scoped($active)->count(),
                'completed' => $scoped($completed)->count(),
            ],
        ]);
    }

    public function show(string $slug): View
    {
        $opportunity = FinancialAidOpportunity::query()
            ->where('slug', $slug)
            ->whereIn('status', ['published', 'closed'])
            ->firstOrFail();

        $isOpen = $opportunity->isOpenForApplications();

        return view('pages.financial-aid.show', [
            'opportunity' => $opportunity,
            'isOpen' => $isOpen,
        ]);
    }

    public function apply(string $slug): View
    {
        // Resolved the same way the detail page is, so a round that closed while somebody was
        // reading it answers with the reason instead of a dead link.
        $opportunity = FinancialAidOpportunity::query()
            ->where('slug', $slug)
            ->whereIn('status', ['published', 'closed'])
            ->firstOrFail();

        if (!$opportunity->isOpenForApplications()) {
            return redirect()->route('financial-aid.show', $slug)
                ->withErrors(['application' => 'Applications are not currently open for this opportunity.']);
        }

        return view('pages.financial-aid.apply', [
            'opportunity' => $opportunity,
        ]);
    }

    public function submitApplication(Request $request, string $slug): \Illuminate\Http\RedirectResponse
    {
        $opportunity = FinancialAidOpportunity::query()
            ->where('slug', $slug)
            ->whereIn('status', ['published', 'closed'])
            ->firstOrFail();

        if (!$opportunity->isOpenForApplications()) {
            return back()->withErrors(['application' => 'Applications are not currently open for this opportunity.']);
        }

        $validated = $request->validate([
            'student_name' => ['required', 'string', 'max:300'],
            'student_email' => ['required', 'email', 'max:255'],
            'student_phone' => ['nullable', 'string', 'max:30'],
            'student_number' => ['nullable', 'string', 'max:50'],
            'program_applied' => ['nullable', 'string', 'max:300'],
            'personal_statement' => ['required', 'string'],
            'financial_need_statement' => ['required', 'string'],
            'supporting_documents.*' => ['nullable', 'file', 'mimes:pdf,doc,docx,jpg,jpeg,png', 'max:5120'],
        ]);

        // Handle file uploads
        $documents = [];
        if ($request->hasFile('supporting_documents')) {
            foreach ($request->file('supporting_documents') as $file) {
                $path = $file->store('financial-aid/documents', 'public');
                $documents[] = [
                    'name' => $file->getClientOriginalName(),
                    'path' => $path,
                    'mime' => $file->getMimeType(),
                    'size' => $file->getSize(),
                ];
            }
        }

        $studentId = auth()->check() ? auth()->id() : null;

        \App\Models\FinancialAidApplication::create([
            'financial_aid_opportunity_id' => $opportunity->id,
            'student_id' => $studentId,
            'student_name' => $validated['student_name'],
            'student_email' => $validated['student_email'],
            'student_phone' => $validated['student_phone'] ?? null,
            'student_number' => $validated['student_number'] ?? null,
            'program_applied' => $validated['program_applied'] ?? null,
            'personal_statement' => $validated['personal_statement'],
            'financial_need_statement' => $validated['financial_need_statement'],
            'supporting_documents' => $documents,
            'status' => 'pending',
        ]);

        return redirect()->route('financial-aid.show', $opportunity->slug)
            ->with('status', 'Your application has been submitted successfully. Our team will review it and get back to you.');
    }
}