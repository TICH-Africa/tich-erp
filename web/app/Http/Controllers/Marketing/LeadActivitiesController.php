<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Models\Marketing\LeadActivity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LeadActivitiesController extends Controller
{
    public function index(Request $request): View
    {
        $query = LeadActivity::query()->with(['lead', 'createdBy']);

        if ($leadId = (string) $request->string('lead_id')->trim()) {
            $query->where('lead_id', (int) $leadId);
        }

        if ($completed = (string) $request->string('completed')->trim()) {
            $query->where('completed', $completed === '1');
        }

        $activities = $query->orderBy('scheduled_date')->paginate(50);
        $leads = \App\Models\Marketing\Lead::query()->orderBy('name')->get();

        return view('marketing.lead-activities.index', [
            'activities' => $activities,
            'leads' => $leads,
            'filters' => $request->only(['lead_id', 'completed']),
        ]);
    }

    public function create(): View
    {
        return view('marketing.lead-activities.create', [
            'leads' => \App\Models\Marketing\Lead::query()->orderBy('name')->get(),
            'activityTypes' => ['call', 'meeting', 'email', 'visit', 'event', 'followup', 'other'],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'lead_id' => ['required', 'exists:marketing_leads,id'],
            'activity_type' => ['required', 'string', 'max:50'],
            'scheduled_date' => ['required', 'date'],
            'scheduled_time' => ['nullable', 'time'],
            'description' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ]);

        LeadActivity::create([
            ...$validated,
            'created_by' => $request->user()?->staff_id,
        ]);

        return redirect()->route('marketing.lead-activities.index')->with('status', 'Activity created.');
    }

    public function edit(LeadActivity $activity): View
    {
        return view('marketing.lead-activities.edit', [
            'activity' => $activity,
            'leads' => \App\Models\Marketing\Lead::query()->orderBy('name')->get(),
            'activityTypes' => ['call', 'meeting', 'email', 'visit', 'event', 'followup', 'other'],
        ]);
    }

    public function update(Request $request, LeadActivity $activity): RedirectResponse
    {
        $validated = $request->validate([
            'lead_id' => ['required', 'exists:marketing_leads,id'],
            'activity_type' => ['required', 'string', 'max:50'],
            'scheduled_date' => ['required', 'date'],
            'scheduled_time' => ['nullable', 'time'],
            'description' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'completed' => ['required', 'boolean'],
        ]);

        $activity->update([
            ...$validated,
            'updated_by' => $request->user()?->staff_id,
        ]);

        return redirect()->route('marketing.lead-activities.edit', $activity)->with('status', 'Activity updated.');
    }

    public function destroy(LeadActivity $activity): RedirectResponse
    {
        $activity->delete();

        return redirect()->route('marketing.lead-activities.index')->with('status', 'Activity deleted.');
    }
}
