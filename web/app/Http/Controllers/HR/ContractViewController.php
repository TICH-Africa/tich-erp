<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
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
        $departments = Department::orderBy('dept_name')->get(['id', 'dept_name']);

        return view('hr.contracts.create', [
            'staff' => $staff,
            'departments' => $departments,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'staff_id' => 'required|exists:staff,id',
            'contract_type' => 'required|string|in:permanent,contract,intern,visiting,casual,probation,consultancy',
            'job_title' => 'required|string|max:200',
            'department_id' => 'required|exists:departments,id',
            'gross_salary' => 'required|numeric|min:0',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date',
            'is_renewable' => 'boolean',
            'probation_end_date' => 'nullable|date',
            'contract_document_path' => 'nullable|string|max:500',
        ]);

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
        $departments = Department::orderBy('dept_name')->get(['id', 'dept_name']);

        return view('hr.contracts.edit', [
            'contract' => $contract,
            'staff' => $staff,
            'departments' => $departments,
        ]);
    }

    public function update(Request $request, int $id)
    {
        $contract = StaffContract::findOrFail($id);

        $validated = $request->validate([
            'contract_type' => 'sometimes|string|in:permanent,contract,intern,visiting,casual,probation,consultancy',
            'job_title' => 'sometimes|string|max:200',
            'department_id' => 'sometimes|exists:departments,id',
            'gross_salary' => 'sometimes|numeric|min:0',
            'start_date' => 'sometimes|date',
            'end_date' => 'nullable|date',
            'is_renewable' => 'sometimes|boolean',
            'probation_end_date' => 'nullable|date',
            'probation_status' => 'sometimes|string|in:not_applicable,active,passed,failed',
            'contract_document_path' => 'nullable|string|max:500',
            'is_signed' => 'sometimes|boolean',
            'signed_date' => 'nullable|date',
            'witnessed_by' => 'nullable|string|max:200',
        ]);

        app(\App\Services\ContractService::class)->updateContract($id, $validated, $request->user()->id);

        return redirect()->route('hr.contracts.show', $contract)->with('success', 'Contract updated successfully.');
    }

    public function destroy(Request $request, int $id)
    {
        $contract = StaffContract::findOrFail($id);

        app(\App\Services\ContractService::class)->terminateContract($id, 'Deleted by user', $request->user()->id);
        $contract->delete();

        return redirect()->route('hr.contracts.index')->with('success', 'Contract deleted successfully.');
    }
}
