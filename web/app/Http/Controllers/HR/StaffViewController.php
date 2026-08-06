<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Staff;
use App\Models\StaffOnboarding;
use App\Services\AuditService;
use App\Services\StaffLifecycleService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;

class StaffViewController extends Controller
{
    public function __construct(
        protected AuditService $auditService,
        protected StaffLifecycleService $staffLifecycle,
    ) {}

    public function index(): View
    {
        $departments = Department::orderBy('dept_name')->get(['id', 'dept_name']);
        $query = Staff::with(['department', 'campus', 'lineManager']);

        if ($search = request('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('employee_number', 'like', "%{$search}%")
                    ->orWhere('first_name', 'like', "%{$search}%")
                    ->orWhere('middle_name', 'like', "%{$search}%")
                    ->orWhere('surname', 'like', "%{$search}%")
                    ->orWhere('primary_email', 'like', "%{$search}%")
                    ->orWhere('organisation_email', 'like', "%{$search}%");
            });
        }

        if ($status = request('status')) {
            $query->where('employment_status', $status);
        }

        if ($departmentId = request('department_id')) {
            $query->where('department_id', $departmentId);
        }

        $staff = $query->orderByDesc('created_at')->paginate(25)->appends(request()->query());

        return view('hr.staff.index', [
            'staff' => $staff,
            'departments' => $departments,
        ]);
    }

    public function create(): View
    {
        $departments = Department::orderBy('dept_name')->get(['id', 'dept_name']);
        $campuses = \App\Models\Campus::orderBy('campus_name')->get(['id', 'campus_name']);
        $lineManagers = Staff::whereIn('employment_status', ['active', 'onboarding'])
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'surname', 'employee_number']);

        return view('hr.staff.create', [
            'departments' => $departments,
            'campuses' => $campuses,
            'lineManagers' => $lineManagers,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'nullable|string|max:100',
            'first_name' => 'required|string|max:100',
            'middle_name' => 'nullable|string|max:100',
            'surname' => 'required|string|max:100',
            'date_of_birth' => 'required|date',
            'gender' => 'required|string|max:20',
            'marital_status' => 'nullable|string|max:50',
            'national_id_number' => 'nullable|string|max:50|unique:staff,national_id_number',
            'passport_number' => 'nullable|string|max:50|unique:staff,passport_number',
            'nationality' => 'nullable|string|max:100|default:Kenyan',
            'home_county' => 'nullable|string|max:100',
            'primary_email' => 'required|email|max:255',
            'organisation_email' => 'nullable|email|max:255|regex:/@tich\.africa$/i|unique:staff,organisation_email',
            'phone_number' => 'required|string|max:30',
            'alt_phone_number' => 'nullable|string|max:30',
            'postal_address' => 'nullable|string|max:300',
            'postal_code' => 'nullable|string|max:20',
            'physical_address' => 'nullable|string|max:500',
            'emergency_contact_name' => 'nullable|string|max:300',
            'emergency_contact_phone' => 'nullable|string|max:30',
            'emergency_contact_relationship' => 'nullable|string|max:50',
            'department_id' => 'required|exists:departments,id',
            'campus_id' => 'nullable|exists:campuses,id',
            'job_title' => 'required|string|max:200',
            'job_grade' => 'nullable|string|max:20',
            'employment_category' => 'required|string|in:'.implode(',', array_keys(config('tich-payroll.employment_categories', []))),
            'payroll_scheme' => 'required|string|in:'.implode(',', array_keys(config('tich-payroll.payroll_schemes', []))),
            'employment_start_date' => 'required|date',
            'contract_end_date' => 'nullable|date',
            'is_on_probation' => 'boolean',
            'probation_end_date' => 'nullable|date',
            'gross_monthly_salary' => 'required|numeric|min:0',
            'bank_id' => 'nullable|exists:staff_bank_accounts,id',
            'kra_pin' => 'nullable|string|max:50',
            'nssf_number' => 'nullable|string|max:50',
            'sha_number' => 'nullable|string|max:50',
            'helb_number' => 'nullable|string|max:50',
            'pension_scheme_id' => 'nullable|exists:pension_schemes,id',
            'is_teaching_staff' => 'boolean',
            'is_nursing_license_required' => 'boolean',
            'line_manager_id' => 'nullable|exists:staff,id',
            'salary_scale' => 'nullable|string|max:50',
            'incremental_date' => 'nullable|date',
            'project_code' => 'nullable|string|max:100',
        ]);

        $validated['employment_status'] = 'onboarding';
        $validated['is_on_probation'] = $request->boolean('is_on_probation');
        $validated['employee_number'] = $this->staffLifecycle->generateEmployeeNumber();
        $validated = $this->prepareStaffEmails($validated);

        DB::transaction(function () use ($validated, $request) {
            $staff = Staff::create($validated);
            $staff->syncLinkedUserEmail();

            StaffOnboarding::create([
                'staff_id' => $staff->id,
                'onboarding_number' => 'ONB-' . strtoupper(\Illuminate\Support\Str::random(8)),
                'current_step' => 'biodata',
                'status' => 'in_progress',
                'completed_steps' => ['biodata'],
            ]);

            $this->auditService->log(
                'staff.created',
                'staff',
                $staff->id,
                null,
                $staff->toArray(),
                'Staff record created',
                'success',
                $request->user()->id,
                $request
            );
        });

        return redirect()->route('hr.staff.index')->with('success', 'Staff member created successfully.');
    }

    public function show(int $id): View
    {
        $staff = Staff::with([
            'department',
            'campus',
            'lineManager',
            'user',
            'bankAccount',
            'pensionScheme',
            'nextOfKin',
            'primaryNextOfKin',
            'allowances',
            'activeAllowances',
            'documents',
            'contracts',
            'qualifications',
            'professionalLicenses',
            'statusHistory',
            'latestOnboarding',
        ])->findOrFail($id);

        return view('hr.staff.show', ['staff' => $staff]);
    }

    public function edit(int $id): View
    {
        $staff = Staff::findOrFail($id);
        $departments = Department::orderBy('dept_name')->get(['id', 'dept_name']);
        $campuses = \App\Models\Campus::orderBy('campus_name')->get(['id', 'campus_name']);
        $lineManagers = Staff::where('id', '!=', $id)
            ->whereIn('employment_status', ['active', 'onboarding'])
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'surname', 'employee_number']);

        return view('hr.staff.edit', [
            'staff' => $staff,
            'departments' => $departments,
            'campuses' => $campuses,
            'lineManagers' => $lineManagers,
        ]);
    }

    public function update(Request $request, int $id)
    {
        $staff = Staff::findOrFail($id);

        $validated = $request->validate([
            'title' => 'nullable|string|max:100',
            'first_name' => 'sometimes|string|max:100',
            'middle_name' => 'nullable|string|max:100',
            'surname' => 'sometimes|string|max:100',
            'date_of_birth' => 'sometimes|date',
            'gender' => 'sometimes|string|max:20',
            'marital_status' => 'nullable|string|max:50',
            'national_id_number' => 'nullable|string|max:50|unique:staff,national_id_number,' . $staff->id,
            'passport_number' => 'nullable|string|max:50|unique:staff,passport_number,' . $staff->id,
            'nationality' => 'nullable|string|max:100',
            'home_county' => 'nullable|string|max:100',
            'primary_email' => 'sometimes|email|max:255',
            'organisation_email' => 'sometimes|email|max:255|regex:/@tich\.africa$/i|unique:staff,organisation_email,'.$staff->id,
            'phone_number' => 'sometimes|string|max:30',
            'alt_phone_number' => 'nullable|string|max:30',
            'postal_address' => 'nullable|string|max:300',
            'postal_code' => 'nullable|string|max:20',
            'physical_address' => 'nullable|string|max:500',
            'emergency_contact_name' => 'nullable|string|max:300',
            'emergency_contact_phone' => 'nullable|string|max:30',
            'emergency_contact_relationship' => 'nullable|string|max:50',
            'department_id' => 'sometimes|exists:departments,id',
            'campus_id' => 'nullable|exists:campuses,id',
            'job_title' => 'sometimes|string|max:200',
            'job_grade' => 'nullable|string|max:20',
            'employment_category' => 'sometimes|string|in:'.implode(',', array_keys(config('tich-payroll.employment_categories', []))),
            'payroll_scheme' => 'sometimes|string|in:'.implode(',', array_keys(config('tich-payroll.payroll_schemes', []))),
            'employment_start_date' => 'sometimes|date',
            'contract_end_date' => 'nullable|date',
            'is_on_probation' => 'boolean',
            'probation_end_date' => 'nullable|date',
            'gross_monthly_salary' => 'sometimes|numeric|min:0',
            'bank_id' => 'nullable|exists:staff_bank_accounts,id',
            'kra_pin' => 'nullable|string|max:50',
            'nssf_number' => 'nullable|string|max:50',
            'sha_number' => 'nullable|string|max:50',
            'helb_number' => 'nullable|string|max:50',
            'pension_scheme_id' => 'nullable|exists:pension_schemes,id',
            'is_teaching_staff' => 'boolean',
            'is_nursing_license_required' => 'boolean',
            'line_manager_id' => 'nullable|exists:staff,id',
            'salary_scale' => 'nullable|string|max:50',
            'incremental_date' => 'nullable|date',
            'project_code' => 'nullable|string|max:100',
        ]);

        $validated['is_on_probation'] = $request->boolean('is_on_probation');
        $validated = $this->prepareStaffEmails($validated, $staff);

        DB::transaction(function () use ($staff, $validated, $request) {
            $staff->update($validated);
            $staff->syncLinkedUserEmail();

            $this->auditService->log(
                'staff.updated',
                'staff',
                $staff->id,
                $staff->getOriginal(),
                $staff->fresh()->toArray(),
                'Staff record updated',
                'success',
                $request->user()->id,
                $request
            );
        });

        return redirect()->route('hr.staff.show', $staff)->with('success', 'Staff member updated successfully.');
    }

    public function destroy(Request $request, int $id)
    {
        $staff = Staff::findOrFail($id);

        DB::transaction(function () use ($staff, $request) {
            $this->auditService->log(
                'staff.deleted',
                'staff',
                $staff->id,
                $staff->toArray(),
                null,
                'Staff record deleted',
                'success',
                $request->user()->id,
                $request
            );

            $staff->delete();
        });

        return redirect()->route('hr.staff.index')->with('success', 'Staff member deleted successfully.');
    }

    private function prepareStaffEmails(array $validated, ?Staff $staff = null): array
    {
        if (empty($validated['organisation_email'])) {
            $validated['organisation_email'] = Staff::organisationEmailFromName(
                $validated['first_name'] ?? $staff?->first_name ?? 'employee',
                $validated['surname'] ?? $staff?->surname ?? 'staff',
                $staff?->id
            );
        }

        return $validated;
    }
}

