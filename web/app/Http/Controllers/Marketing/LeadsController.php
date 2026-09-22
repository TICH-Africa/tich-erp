<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Models\Marketing\Lead;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LeadsController extends Controller
{
    public function index(Request $request): View
    {
        $query = Lead::query()->with(['program', 'assignedTo']);

        if ($search = (string) $request->string('search')->trim()) {
            $query->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%");
        }

        if ($stage = (string) $request->string('stage')->trim()) {
            $query->where('stage', $stage);
        }

        if ($source = (string) $request->string('source')->trim()) {
            $query->where('source', $source);
        }

        $leads = $query->orderByDesc('created_at')->paginate(50);
        $stages = ['new', 'contacted', 'qualified', 'proposal', 'won', 'lost'];
        $sources = ['referral', 'social_media', 'website', 'event', 'direct', 'other'];

        return view('marketing.leads.index', [
            'leads' => $leads,
            'stages' => $stages,
            'sources' => $sources,
            'filters' => $request->only(['search', 'stage', 'source']),
        ]);
    }

    public function create(): View
    {
        return view('marketing.leads.create', [
            'stages' => ['new', 'contacted', 'qualified', 'proposal', 'won', 'lost'],
            'sources' => ['referral', 'social_media', 'website', 'event', 'direct', 'other'],
            'programs' => \App\Models\AcademicProgram::query()->orderBy('program_name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:300'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'source' => ['nullable', 'string', 'max:50'],
            'stage' => ['required', 'string', 'max:50'],
            'program_id' => ['nullable', 'exists:academic_programs,id'],
            'intake' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
            'next_followup' => ['nullable', 'date'],
            'assigned_to' => ['nullable', 'exists:staff,id'],
        ]);

        Lead::create([
            ...$validated,
            'created_by' => $request->user()?->staff_id,
        ]);

        return redirect()->route('marketing.leads.index')->with('status', 'Lead created.');
    }

    public function edit(Lead $lead): View
    {
        return view('marketing.leads.edit', [
            'lead' => $lead,
            'stages' => ['new', 'contacted', 'qualified', 'proposal', 'won', 'lost'],
            'sources' => ['referral', 'social_media', 'website', 'event', 'direct', 'other'],
            'programs' => \App\Models\AcademicProgram::query()->orderBy('program_name')->get(),
        ]);
    }

    public function update(Request $request, Lead $lead): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:300'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'source' => ['nullable', 'string', 'max:50'],
            'stage' => ['required', 'string', 'max:50'],
            'program_id' => ['nullable', 'exists:academic_programs,id'],
            'intake' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
            'next_followup' => ['nullable', 'date'],
            'assigned_to' => ['nullable', 'exists:staff,id'],
        ]);

        $lead->update([
            ...$validated,
            'updated_by' => $request->user()?->staff_id,
        ]);

        return redirect()->route('marketing.leads.edit', $lead)->with('status', 'Lead updated.');
    }

    public function destroy(Lead $lead): RedirectResponse
    {
        $lead->delete();

        return redirect()->route('marketing.leads.index')->with('status', 'Lead deleted.');
    }
}
