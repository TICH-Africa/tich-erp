<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\DisciplinaryCase;
use App\Models\DisciplinaryDocument;
use App\Models\Staff;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DisciplinaryController extends Controller
{
    public function __construct(
        protected AuditService $auditService,
    ) {}

    public function index(Request $request): View
    {
        $query = DisciplinaryCase::query()
            ->with(['staff', 'assignedTo'])
            ->when($request->filled('search'), function ($q) use ($request) {
                $q->whereHas('staff', function ($sq) use ($request) {
                    $sq->where('first_name', 'like', "%{$request->search}%")
                        ->orWhere('surname', 'like', "%{$request->search}%")
                        ->orWhere('employee_number', 'like', "%{$request->search}%");
                })
                ->orWhere('case_number', 'like', "%{$request->search}%");
            })
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->orderByDesc('incident_date');

        $cases = $query->paginate(20);

        return view('hr.disciplinary.index', [
            'cases' => $cases,
        ]);
    }

    public function create(): View
    {
        $staffList = Staff::query()
            ->orderBy('surname')
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'surname', 'employee_number', 'job_title']);

        return view('hr.disciplinary.create', [
            'staffList' => $staffList,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'staff_id' => 'required|exists:staff,id',
            'assigned_to' => 'nullable|exists:staff,id',
            'incident_date' => 'required|date',
            'incident_description' => 'required|string|max:5000',
            'investigation_notes' => 'nullable|string|max:5000',
            'witness_information' => 'nullable|string|max:3000',
            'hearing_date' => 'nullable|date',
            'committee_members' => 'nullable|string|max:2000',
        ]);

        $caseNumber = 'DISC-' . now()->format('Y') . '-' . strtoupper(Str::random(6));

        $case = DB::transaction(function () use ($validated, $caseNumber) {
            return DisciplinaryCase::create([
                'case_number' => $caseNumber,
                'staff_id' => $validated['staff_id'],
                'assigned_to' => $validated['assigned_to'],
                'incident_date' => $validated['incident_date'],
                'incident_description' => $validated['incident_description'],
                'investigation_notes' => $validated['investigation_notes'],
                'witness_information' => $validated['witness_information'],
                'hearing_date' => $validated['hearing_date'],
                'committee_members' => $validated['committee_members'],
                'status' => 'open',
            ]);
        });

        $this->auditService->log(
            'disciplinary.case.created',
            'disciplinary_cases',
            $case->id,
            null,
            $case->toArray(),
            'Disciplinary case created',
            'success',
            $request->user()->id
        );

        return redirect()->route('hr.disciplinary.index')->with('success', 'Disciplinary case created successfully.');
    }

    public function show(DisciplinaryCase $disciplinaryCase): View
    {
        $case = $disciplinaryCase->load(['staff', 'assignedTo', 'documents']);

        return view('hr.disciplinary.show', [
            'case' => $case,
        ]);
    }

    public function edit(DisciplinaryCase $disciplinaryCase): View
    {
        $case = $disciplinaryCase->load('staff', 'assignedTo');
        $staffList = Staff::query()
            ->orderBy('surname')
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'surname', 'employee_number', 'job_title']);

        return view('hr.disciplinary.edit', [
            'case' => $case,
            'staffList' => $staffList,
        ]);
    }

    public function update(Request $request, DisciplinaryCase $disciplinaryCase)
    {
        $validated = $request->validate([
            'assigned_to' => 'nullable|exists:staff,id',
            'investigation_notes' => 'nullable|string|max:5000',
            'witness_information' => 'nullable|string|max:3000',
            'hearing_date' => 'nullable|date',
            'committee_members' => 'nullable|string|max:2000',
            'decision' => 'nullable|string|max:5000',
            'action_type' => 'nullable|in:warning,suspension,termination,appeal,other',
            'action_details' => 'nullable|string|max:5000',
            'action_start_date' => 'nullable|date',
            'action_end_date' => 'nullable|date',
            'status' => 'required|in:open,under_investigation,hearing_scheduled,decided,appealed,closed',
            'hr_comments' => 'nullable|string|max:5000',
        ]);

        $oldSnapshot = $case->toArray();

        DB::transaction(function () use ($disciplinaryCase, $validated) {
            $disciplinaryCase->update($validated);
        });

        $this->auditService->log(
            'disciplinary.case.updated',
            'disciplinary_cases',
            $disciplinaryCase->id,
            $oldSnapshot,
            $disciplinaryCase->fresh()->toArray(),
            'Disciplinary case updated',
            'success',
            $request->user()->id
        );

        return redirect()->route('hr.disciplinary.index')->with('success', 'Disciplinary case updated successfully.');
    }

    public function destroy(Request $request, DisciplinaryCase $disciplinaryCase)
    {
        DB::transaction(function () use ($disciplinaryCase, $request) {
            $this->auditService->log(
                'disciplinary.case.deleted',
                'disciplinary_cases',
                $disciplinaryCase->id,
                $disciplinaryCase->toArray(),
                null,
                'Disciplinary case deleted',
                'success',
                $request->user()->id
            );

            $disciplinaryCase->delete();
        });

        return redirect()->route('hr.disciplinary.index')->with('success', 'Disciplinary case deleted successfully.');
    }
}
