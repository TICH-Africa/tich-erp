<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\Campus;
use App\Models\Department;
use App\Models\Staff;
use App\Models\StaffContract;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ContractViewController extends Controller
{
    public function index(): View
    {
        $contracts = StaffContract::with(['staff', 'staff.department', 'department'])
            ->orderByDesc('start_date')
            ->paginate(25);

        return view('hr.contracts.index', ['contracts' => $contracts]);
    }

    public function create(): View
    {
        $staff = Staff::orderBy('first_name')->get(['id', 'first_name', 'surname', 'employee_number', 'job_title']);
        $departments = Department::assignableForHr()->active()->orderBy('dept_name')->get(['id', 'dept_name']);
        $campuses = Campus::orderBy('campus_name')->get(['id', 'campus_name']);

        return view('hr.contracts.create', [
            'staff' => $staff,
            'departments' => $departments,
            'campuses' => $campuses,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'staff_id' => 'required|exists:staff,id',
            'contract_type' => 'required|string|in:permanent,contract,intern,visiting,casual,probation,consultancy',
            'job_title' => 'required|string|max:200',
            'department_id' => 'required|exists:departments,id',
            'campus_id' => 'nullable|exists:campuses,id',
            'job_grade' => 'nullable|string|max:50',
            'payroll_scheme' => 'nullable|string|max:50',
            'salary_scale' => 'nullable|string|max:50',
            'line_manager_id' => 'nullable|exists:staff,id',
            'organisation_email' => 'nullable|email|max:255',
            'gross_salary' => 'required|numeric|min:0',
            'start_date' => 'required|date',
            'duration' => 'nullable|string|max:50',
            'end_date' => 'nullable|date',
            'is_renewable' => 'boolean',
            'probation_end_date' => 'nullable|date',
            'contract_document_path' => 'nullable|string|max:500',
        ]);

        if (empty($validated['end_date']) && ! empty($validated['duration'])) {
            $calculated = app(\App\Services\ContractService::class)->calculateEndDate($validated['start_date'], $validated['duration']);
            if ($calculated) {
                $validated['end_date'] = $calculated;
            }
        }

        $contract = app(\App\Services\ContractService::class)->createContract($validated['staff_id'], $validated, $request->user()->id);

        return redirect()->route('hr.contracts.show', $contract)->with('success', 'Contract created successfully.');
    }

    public function show(int $id): View
    {
        $contract = StaffContract::with([
            'staff',
            'staff.department',
            'department',
            'newContract',
            'previousContract',
        ])->findOrFail($id);

        return view('hr.contracts.show', ['contract' => $contract]);
    }

    public function edit(int $id): View
    {
        $contract = StaffContract::findOrFail($id);
        $staff = Staff::orderBy('first_name')->get(['id', 'first_name', 'surname', 'employee_number', 'job_title']);
        $departments = Department::assignableForHr()->active()->orderBy('dept_name')->get(['id', 'dept_name']);
        $campuses = Campus::orderBy('campus_name')->get(['id', 'campus_name']);

        return view('hr.contracts.edit', [
            'contract' => $contract,
            'staff' => $staff,
            'departments' => $departments,
            'campuses' => $campuses,
        ]);
    }

    public function update(Request $request, int $id)
    {
        $contract = StaffContract::findOrFail($id);

        $validated = $request->validate([
            'contract_type' => 'sometimes|string|in:permanent,contract,intern,visiting,casual,probation,consultancy',
            'job_title' => 'sometimes|string|max:200',
            'department_id' => 'sometimes|exists:departments,id',
            'campus_id' => 'nullable|exists:campuses,id',
            'job_grade' => 'nullable|string|max:50',
            'payroll_scheme' => 'nullable|string|max:50',
            'salary_scale' => 'nullable|string|max:50',
            'line_manager_id' => 'nullable|exists:staff,id',
            'organisation_email' => 'nullable|email|max:255',
            'gross_salary' => 'sometimes|numeric|min:0',
            'start_date' => 'sometimes|date',
            'duration' => 'nullable|string|max:50',
            'end_date' => 'nullable|date',
            'is_renewable' => 'sometimes|boolean',
            'probation_end_date' => 'nullable|date',
            'probation_status' => 'sometimes|string|in:not_applicable,active,passed,failed',
            'contract_document_path' => 'nullable|string|max:500',
            'is_signed' => 'sometimes|boolean',
            'signed_date' => 'nullable|date',
            'witnessed_by' => 'nullable|string|max:200',
        ]);

        if (empty($validated['end_date']) && ! empty($validated['duration']) && ! empty($validated['start_date'])) {
            $startDate = $validated['start_date'] ?? $contract->start_date;
            if ($startDate) {
                $calculated = app(\App\Services\ContractService::class)->calculateEndDate($startDate, $validated['duration']);
                if ($calculated) {
                    $validated['end_date'] = $calculated;
                }
            }
        } elseif (empty($validated['end_date']) && ! empty($validated['duration']) && ! empty($contract->start_date)) {
            $calculated = app(\App\Services\ContractService::class)->calculateEndDate($contract->start_date, $validated['duration']);
            if ($calculated) {
                $validated['end_date'] = $calculated;
            }
        }

        app(\App\Services\ContractService::class)->updateContract($id, $validated, $request->user()->id);

        return redirect()->route('hr.contracts.show', $contract)->with('success', 'Contract updated successfully.');
    }

    public function renew(Request $request, int $id)
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'duration' => 'nullable|string|max:50',
            'end_date' => 'nullable|date',
            'gross_salary' => 'required|numeric|min:0',
            'job_title' => 'sometimes|string|max:200',
            'contract_type' => 'sometimes|string|in:permanent,contract,intern,visiting,casual,probation,consultancy',
        ]);

        if (empty($validated['end_date']) && ! empty($validated['duration'])) {
            $calculated = app(\App\Services\ContractService::class)->calculateEndDate($validated['start_date'], $validated['duration']);
            if ($calculated) {
                $validated['end_date'] = $calculated;
            }
        }

        $newContract = $this->contractService->renewContract($id, $validated, $request->user()->id);

        return redirect()->route('hr.contracts.show', $newContract)->with('success', 'Contract renewed successfully.');
    }

    public function destroy(Request $request, int $id)
    {
        $contract = StaffContract::findOrFail($id);

        app(\App\Services\ContractService::class)->terminateContract($id, 'Deleted by user', $request->user()->id);
        $contract->delete();

        return redirect()->route('hr.contracts.index')->with('success', 'Contract deleted successfully.');
    }
}
