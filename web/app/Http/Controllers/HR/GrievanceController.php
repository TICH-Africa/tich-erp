<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\Grievance;
use App\Models\Staff;
use App\Services\AuditService;
use App\Services\EmployeeConcernService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;

class GrievanceController extends Controller
{
    public function __construct(
        protected AuditService $auditService,
        protected EmployeeConcernService $concernService,
    ) {}

    public function index(Request $request): View
    {
        $query = Grievance::query()
            ->with(['staff', 'assignedTo'])
            ->when($request->filled('search'), function ($q) use ($request) {
                $q->whereHas('staff', function ($sq) use ($request) {
                    $sq->where('first_name', 'like', "%{$request->search}%")
                        ->orWhere('surname', 'like', "%{$request->search}%")
                        ->orWhere('employee_number', 'like', "%{$request->search}%");
                });
            })
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->orderByDesc('created_at');

        $grievances = $query->paginate(20);

        return view('hr.grievances.index', [
            'grievances' => $grievances,
        ]);
    }

    public function create(): View
    {
        $staffList = Staff::query()
            ->orderBy('surname')
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'surname', 'employee_number', 'job_title']);

        return view('hr.grievances.create', [
            'staffList' => $staffList,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'staff_id' => 'required|exists:staff,id',
            'assigned_to' => 'nullable|exists:staff,id',
            'grievance_type' => 'nullable|string|max:100',
            'subject' => 'nullable|string|max:300',
            'description' => 'required|string|max:5000',
            'incident_date' => 'nullable|date',
            'resolution_notes' => 'nullable|string|max:5000',
        ]);

        $grievance = DB::transaction(function () use ($validated) {
            return Grievance::create([
                ...$validated,
                'reference_number' => $this->concernService->generateReferenceNumber(),
                'status' => 'open',
            ]);
        });

        $this->auditService->log(
            'grievance.created',
            'grievances',
            $grievance->id,
            null,
            $grievance->toArray(),
            'Grievance created',
            'success',
            $request->user()->id
        );

        return redirect()->route('hr.employee-relations.grievances.index')->with('success', 'Grievance created successfully.');
    }

    public function show(Grievance $grievance): View
    {
        $grievance->load(['staff', 'assignedTo']);

        return view('hr.grievances.show', [
            'grievance' => $grievance,
        ]);
    }

    public function edit(Grievance $grievance): View
    {
        $grievance->load('staff', 'assignedTo');
        $staffList = Staff::query()
            ->orderBy('surname')
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'surname', 'employee_number', 'job_title']);

        return view('hr.grievances.edit', [
            'grievance' => $grievance,
            'staffList' => $staffList,
        ]);
    }

    public function update(Request $request, Grievance $grievance)
    {
        $validated = $request->validate([
            'assigned_to' => 'nullable|exists:staff,id',
            'grievance_type' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:5000',
            'incident_date' => 'nullable|date',
            'resolution_notes' => 'nullable|string|max:5000',
            'status' => 'required|in:open,under_review,resolved,closed',
            'response' => 'nullable|string|max:5000',
            'resolved_at' => 'nullable|date',
            'hr_comments' => 'nullable|string|max:5000',
        ]);

        $oldSnapshot = $grievance->toArray();

        DB::transaction(function () use ($grievance, $validated) {
            $grievance->update($validated);
        });

        $this->auditService->log(
            'grievance.updated',
            'grievances',
            $grievance->id,
            $oldSnapshot,
            $grievance->fresh()->toArray(),
            'Grievance updated',
            'success',
            $request->user()->id
        );

        return redirect()->route('hr.employee-relations.grievances.index')->with('success', 'Grievance updated successfully.');
    }

    public function destroy(Request $request, Grievance $grievance)
    {
        DB::transaction(function () use ($grievance, $request) {
            $this->auditService->log(
                'grievance.deleted',
                'grievances',
                $grievance->id,
                $grievance->toArray(),
                null,
                'Grievance deleted',
                'success',
                $request->user()->id
            );

            $grievance->delete();
        });

        return redirect()->route('hr.employee-relations.grievances.index')->with('success', 'Grievance deleted successfully.');
    }
}
