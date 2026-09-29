<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\FinancialAidOpportunity;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FinancialAidController extends Controller
{
    public function index(): View
    {
        $opportunities = FinancialAidOpportunity::query()
            ->where('status', 'published')
            ->where(function ($query) {
                $query->whereNull('application_deadline')
                      ->orWhere('application_deadline', '>=', now());
            })
            ->where(function ($query) {
                $query->whereNull('application_open_date')
                      ->orWhere('application_open_date', '<=', now());
            })
            ->orderByDesc('published_at')
            ->paginate(10);

        return view('pages.financial-aid.index', [
            'opportunities' => $opportunities,
        ]);
    }

    public function show(string $slug): View
    {
        $opportunity = FinancialAidOpportunity::query()
            ->where('slug', $slug)
            ->where('status', 'published')
            ->where(function ($query) {
                $query->whereNull('application_deadline')
                      ->orWhere('application_deadline', '>=', now());
            })
            ->where(function ($query) {
                $query->whereNull('application_open_date')
                      ->orWhere('application_open_date', '<=', now());
            })
            ->firstOrFail();

        $isOpen = $opportunity->isOpenForApplications();

        return view('pages.financial-aid.show', [
            'opportunity' => $opportunity,
            'isOpen' => $isOpen,
        ]);
    }

    public function apply(string $slug): View
    {
        $opportunity = FinancialAidOpportunity::query()
            ->where('slug', $slug)
            ->where('status', 'published')
            ->where(function ($query) {
                $query->whereNull('application_deadline')
                      ->orWhere('application_deadline', '>=', now());
            })
            ->where(function ($query) {
                $query->whereNull('application_open_date')
                      ->orWhere('application_open_date', '<=', now());
            })
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
            ->where('status', 'published')
            ->where(function ($query) {
                $query->whereNull('application_deadline')
                      ->orWhere('application_deadline', '>=', now());
            })
            ->where(function ($query) {
                $query->whereNull('application_open_date')
                      ->orWhere('application_open_date', '<=', now());
            })
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