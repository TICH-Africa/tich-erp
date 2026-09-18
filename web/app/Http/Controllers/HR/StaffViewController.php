<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\PensionScheme;
use App\Models\Staff;
use App\Models\StaffBankAccount;
use App\Models\StaffOnboarding;
use App\Services\AuditService;
use App\Services\EmployeeProfileChangeService;
use App\Services\StaffLifecycleService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class StaffViewController extends Controller
{
    public function __construct(
        protected AuditService $auditService,
        protected StaffLifecycleService $staffLifecycle,
        protected EmployeeProfileChangeService $profileChanges,
    ) {}

    public function index(): View
    {
        $departments = Department::assignableForHr()->active()->orderBy('dept_name')->get(['id', 'dept_name']);
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
        $departments = Department::assignableForHr()->active()->orderBy('dept_name')->get(['id', 'dept_name']);
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
            'phone_number' => 'required|string|max:30',
            'alt_phone_number' => 'nullable|string|max:30',
            'postal_address' => 'nullable|string|max:300',
            'postal_code' => 'nullable|string|max:20',
            'physical_address' => 'nullable|string|max:500',
            'emergency_contact_name' => 'nullable|string|max:300',
            'emergency_contact_phone' => 'nullable|string|max:30',
            'emergency_contact_relationship' => 'nullable|string|max:50',
            'department_id' => 'nullable|exists:departments,id',
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
        $staff = Staff::with(['bankAccount', 'pensionScheme'])->findOrFail($id);
        $departments = Department::assignableForHr()->active()->orderBy('dept_name')->get(['id', 'dept_name']);
        $campuses = \App\Models\Campus::orderBy('campus_name')->get(['id', 'campus_name']);
        $lineManagers = Staff::where('id', '!=', $id)
            ->whereIn('employment_status', ['active', 'onboarding'])
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'surname', 'employee_number']);
        $pensionSchemes = PensionScheme::query()
            ->where(function ($query) use ($staff) {
                $query->where('is_active', true);
                if ($staff->pension_scheme_id) {
                    $query->orWhere('id', $staff->pension_scheme_id);
                }
            })
            ->orderBy('scheme_name')
            ->get(['id', 'scheme_name']);

        return view('hr.staff.edit', [
            'staff' => $staff,
            'departments' => $departments,
            'campuses' => $campuses,
            'lineManagers' => $lineManagers,
            'pensionSchemes' => $pensionSchemes,
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
            'phone_number' => 'sometimes|string|max:30',
            'alt_phone_number' => 'nullable|string|max:30',
            'postal_address' => 'nullable|string|max:300',
            'postal_code' => 'nullable|string|max:20',
            'physical_address' => 'nullable|string|max:500',
            'emergency_contact_name' => 'nullable|string|max:300',
            'emergency_contact_phone' => 'nullable|string|max:30',
            'emergency_contact_relationship' => 'nullable|string|max:50',
            'department_id' => 'nullable|exists:departments,id',
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
            'kra_pin' => 'nullable|string|max:50',
            'nssf_number' => 'nullable|string|max:50',
            'sha_number' => 'nullable|string|max:50',
            'helb_number' => 'nullable|string|max:50',
            'pension_scheme_id' => 'nullable|exists:pension_schemes,id',
            'bank_name' => 'nullable|string|max:200',
            'bank_branch' => 'nullable|string|max:200',
            'bank_code' => 'nullable|string|max:20',
            'account_name' => 'nullable|string|max:300',
            'account_number' => 'nullable|string|max:50',
            'is_teaching_staff' => 'boolean',
            'is_nursing_license_required' => 'boolean',
            'line_manager_id' => 'nullable|exists:staff,id',
            'salary_scale' => 'nullable|string|max:50',
            'incremental_date' => 'nullable|date',
            'project_code' => 'nullable|string|max:100',
            'profile_photo' => 'nullable|image|mimes:jpeg,jpg,png,webp|max:6144',
        ]);

        $validated['is_on_probation'] = $request->boolean('is_on_probation');
        $validated = $this->prepareStaffEmails($validated, $staff);
        unset($validated['profile_photo']);

        $bankInput = [
            'bank_name' => trim((string) ($validated['bank_name'] ?? '')),
            'bank_branch' => trim((string) ($validated['bank_branch'] ?? '')),
            'bank_code' => trim((string) ($validated['bank_code'] ?? '')),
            'account_name' => trim((string) ($validated['account_name'] ?? '')),
            'account_number' => trim((string) ($validated['account_number'] ?? '')),
        ];
        unset(
            $validated['bank_name'],
            $validated['bank_branch'],
            $validated['bank_code'],
            $validated['account_name'],
            $validated['account_number'],
            $validated['bank_id'],
        );

        try {
            $photoPath = $this->profileChanges->resolvePhotoPathFromInput($staff, [
                'profile_photo' => $request->file('profile_photo'),
            ]);
        } catch (InvalidArgumentException $e) {
            $message = $e->getMessage();
            if (str_contains($message, 'employee number')) {
                $message = 'This staff record is missing an employee number. Assign one before uploading a photo.';
            }

            return back()
                ->withInput()
                ->withErrors(['profile_photo' => $message]);
        }

        if ($photoPath) {
            $validated['photo_path'] = $photoPath;
        }

        if (array_key_exists('pension_scheme_id', $validated) && $validated['pension_scheme_id'] === '') {
            $validated['pension_scheme_id'] = null;
        }

        try {
            DB::transaction(function () use ($staff, $validated, $bankInput, $request) {
                $this->syncStaffBankAccount($staff, $bankInput);

                if ($staff->bank_id) {
                    $validated['bank_id'] = $staff->bank_id;
                }

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
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withInput()->withErrors($e->errors());
        }
        $message = 'Staff member updated successfully.';
        if ($photoPath) {
            $message .= ' Profile photo saved.';
        }

        return redirect()->route('hr.staff.show', $staff)->with('success', $message);
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
        // Organisation email is assigned manually by ICT only.
        unset($validated['organisation_email']);

        if (array_key_exists('department_id', $validated) && $validated['department_id'] === '') {
            $validated['department_id'] = null;
        }

        return $validated;
    }

    /**
     * Create or update the staff member's primary bank account from edit-form fields.
     *
     * @param  array{bank_name: string, bank_branch: string, bank_code: string, account_name: string, account_number: string}  $bankInput
     */
    private function syncStaffBankAccount(Staff $staff, array $bankInput): void
    {
        $hasAnyValue = collect($bankInput)->contains(fn (string $value) => $value !== '');

        if (! $hasAnyValue) {
            return;
        }

        $required = ['bank_name', 'bank_code', 'account_name', 'account_number'];
        foreach ($required as $field) {
            if ($bankInput[$field] === '') {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    $field => 'Bank name, bank code, account name, and account number are required together.',
                ]);
            }
        }

        $payload = [
            'bank_name' => $bankInput['bank_name'],
            'bank_branch' => $bankInput['bank_branch'] !== '' ? $bankInput['bank_branch'] : null,
            'bank_code' => $bankInput['bank_code'],
            'account_name' => $bankInput['account_name'],
            'account_number' => $bankInput['account_number'],
            'is_primary' => true,
            'is_active' => true,
        ];

        $account = $staff->bankAccount;
        if ($account) {
            $account->update($payload);
            return;
        }

        $account = StaffBankAccount::create([
            'staff_id' => $staff->id,
            ...$payload,
            'created_at' => now(),
        ]);

        $staff->bank_id = $account->id;
        $staff->save();
    }
}

