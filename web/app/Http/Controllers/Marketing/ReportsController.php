<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Models\Marketing\Report;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportsController extends Controller
{
    public function index(Request $request): View
    {
        $reports = Report::query()
            ->with(['preparedBy', 'approvedBy'])
            ->orderByDesc('report_date')
            ->paginate(50);

        $types = ['weekly', 'monthly', 'quarterly', 'annual'];

        return view('marketing.reports.index', [
            'reports' => $reports,
            'types' => $types,
        ]);
    }

    public function create(): View
    {
        return view('marketing.reports.create', [
            'types' => ['weekly', 'monthly', 'quarterly', 'annual'],
            'staff' => \App\Models\Staff::query()->orderBy('first_name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'report_type' => ['required', 'string', 'in:weekly,monthly,quarterly,annual'],
            'title' => ['required', 'string', 'max:300'],
            'report_date' => ['required', 'date'],
            'summary' => ['nullable', 'string'],
            'prepared_by' => ['nullable', 'exists:staff,id'],
            'commentary' => ['nullable', 'string'],
            'anomalies' => ['nullable', 'string'],
        ]);

        $report = Report::create([
            ...$validated,
            'prepared_by' => $validated['prepared_by'] ?? $request->user()?->staff_id,
            'created_by' => $request->user()?->staff_id,
            'status' => 'draft',
        ]);

        // Handle attachments
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('marketing/reports/' . $report->id, 'public');
                $report->attachments()->create([
                    'file_path' => $path,
                    'original_name' => $file->getClientOriginalName(),
                    'mime_type' => $file->getMimeType(),
                    'size' => $file->getSize(),
                    'uploaded_by' => $request->user()?->staff_id,
                ]);
            }
        }

        return redirect()->route('marketing.reports.index')->with('status', 'Report created.');
    }

    public function show(Report $report): View
    {
        return view('marketing.reports.show', [
            'report' => $report,
        ]);
    }

    public function edit(Report $report): View
    {
        return view('marketing.reports.edit', [
            'report' => $report,
            'types' => ['weekly', 'monthly', 'quarterly', 'annual'],
            'staff' => \App\Models\Staff::query()->orderBy('first_name')->get(),
        ]);
    }

    public function update(Request $request, Report $report): RedirectResponse
    {
        $validated = $request->validate([
            'report_type' => ['required', 'string', 'in:weekly,monthly,quarterly,annual'],
            'title' => ['required', 'string', 'max:300'],
            'report_date' => ['required', 'date'],
            'summary' => ['nullable', 'string'],
            'prepared_by' => ['nullable', 'exists:staff,id'],
            'commentary' => ['nullable', 'string'],
            'anomalies' => ['nullable', 'string'],
        ]);

        $report->update([
            ...$validated,
            'prepared_by' => $validated['prepared_by'] ?? $report->prepared_by,
            'updated_by' => $request->user()?->staff_id,
        ]);

        // Handle new attachments
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('marketing/reports/' . $report->id, 'public');
                $report->attachments()->create([
                    'file_path' => $path,
                    'original_name' => $file->getClientOriginalName(),
                    'mime_type' => $file->getMimeType(),
                    'size' => $file->getSize(),
                    'uploaded_by' => $request->user()?->staff_id,
                ]);
            }
        }

        return redirect()->route('marketing.reports.edit', $report)->with('status', 'Report updated.');
    }

    public function submit(Request $request, Report $report): RedirectResponse
    {
        $report->update([
            'status' => 'submitted',
            'reviewed_by' => $request->input('reviewed_by'),
        ]);

        return redirect()->route('marketing.reports.show', $report)->with('status', 'Report submitted for review.');
    }

    public function approve(Request $request, Report $report): RedirectResponse
    {
        $report->update([
            'status' => 'approved',
            'approved_by' => $request->user()?->staff_id,
        ]);

        return redirect()->route('marketing.reports.show', $report)->with('status', 'Report approved.');
    }

    public function distribute(Request $request, Report $report): RedirectResponse
    {
        $validated = $request->validate([
            'distribution_list' => ['required', 'string', 'max:1000'],
        ]);

        $report->update([
            'status' => 'distributed',
            'distributed_by' => $request->user()?->staff_id,
            'distribution_list' => $validated['distribution_list'],
        ]);

        return redirect()->route('marketing.reports.show', $report)->with('status', 'Report distributed.');
    }
}
