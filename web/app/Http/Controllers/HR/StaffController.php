<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\Staff;
use App\Services\AuditService;
use App\Services\RBACService;
use App\Services\StaffLifecycleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StaffController extends Controller
{
    public function __construct(
        protected StaffLifecycleService $lifecycleService,
        protected AuditService $auditService,
        protected RBACService $rbacService,
    ) {}

    public function index(Request $request)
    {
        $user = $request->user();
        $query = Staff::query()
            ->with(['department', 'campus', 'lineManager', 'user'])
            ->when($request->search, function ($q, $search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('employee_number', 'like', "%{$search}%")
                        ->orWhere('first_name', 'like', "%{$search}%")
                        ->orWhere('middle_name', 'like', "%{$search}%")
                        ->orWhere('surname', 'like', "%{$search}%")
                        ->orWhere('primary_email', 'like', "%{$search}%")
                        ->orWhere('organisation_email', 'like', "%{$search}%")
                        ->orWhere('national_id_number', 'like', "%{$search}%")
                        ->orWhere('kra_pin', 'like', "%{$search}%");
                });
            })
            ->when($request->status, fn ($q, $status) => $q->where('employment_status', $status))
            ->when($request->department_id, fn ($q, $id) => $q->where('department_id', $id))
            ->when($request->campus_id, fn ($q, $id) => $q->where('campus_id', $id))
            ->when($request->category, fn ($q, $cat) => $q->where('employment_category', $cat))
            ->orderByDesc('created_at');

        $perPage = (int) ($request->per_page ?? 25);
        $staff = $query->paginate($perPage)->appends($request->query());

        return response()->json([
            'data' => $staff->items(),
            'meta' => [
                'total' => $staff->total(),
                'per_page' => $staff->perPage(),
                'current_page' => $staff->currentPage(),
                'last_page' => $staff->lastPage(),
            ],
        ]);
    }

    public function show(int $id)
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
            'disciplinaryCases',
            'professionalDevelopment',
            'statusHistory',
            'latestOnboarding',
        ])->findOrFail($id);

        return response()->json(['data' => $staff]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_number' => 'required|string|max:50|unique:staff,employee_number',
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
            'photo_path' => 'nullable|string|max:500',
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
            'confirmation_date' => 'nullable|date',
            'gross_monthly_salary' => 'required|numeric|min:0',
            'allowances_json' => 'nullable|array',
            'bank_id' => 'nullable|exists:staff_bank_accounts,id',
            'kra_pin' => 'nullable|string|max:50',
            'nssf_number' => 'nullable|string|max:50',
            'sha_number' => 'nullable|string|max:50',
            'helb_number' => 'nullable|string|max:50',
            'pension_scheme_id' => 'nullable|exists:pension_schemes,id',
            'employment_status' => 'string|max:50|default:onboarding',
            'exit_date' => 'nullable|date',
            'exit_reason' => 'nullable|string|max:500',
            'user_id' => 'nullable|exists:users,id',
            'is_teaching_staff' => 'boolean',
            'is_nursing_license_required' => 'boolean',
            'line_manager_id' => 'nullable|exists:staff,id',
            'salary_scale' => 'nullable|string|max:50',
            'incremental_date' => 'nullable|date',
            'project_code' => 'nullable|string|max:100',
        ]);

        $staff = DB::transaction(function () use ($validated, $request) {
            if (empty($validated['employee_number'])) {
                $validated['employee_number'] = $this->lifecycleService->generateEmployeeNumber();
            }

            if (empty($validated['organisation_email'])) {
                $validated['organisation_email'] = Staff::organisationEmailFromName(
                    $validated['first_name'],
                    $validated['surname']
                );
            }

            $staff = Staff::create($validated);
            $staff->syncLinkedUserEmail();

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

            return $staff;
        });

        return response()->json(['data' => $staff->load('department', 'campus', 'lineManager')], 201);
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
            'photo_path' => 'nullable|string|max:500',
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
            'confirmation_date' => 'nullable|date',
            'gross_monthly_salary' => 'sometimes|numeric|min:0',
            'allowances_json' => 'nullable|array',
            'bank_id' => 'nullable|exists:staff_bank_accounts,id',
            'kra_pin' => 'nullable|string|max:50',
            'nssf_number' => 'nullable|string|max:50',
            'sha_number' => 'nullable|string|max:50',
            'helb_number' => 'nullable|string|max:50',
            'pension_scheme_id' => 'nullable|exists:pension_schemes,id',
            'employment_status' => 'sometimes|string|max:50',
            'exit_date' => 'nullable|date',
            'exit_reason' => 'nullable|string|max:500',
            'is_teaching_staff' => 'boolean',
            'is_nursing_license_required' => 'boolean',
            'line_manager_id' => 'nullable|exists:staff,id',
            'salary_scale' => 'nullable|string|max:50',
            'incremental_date' => 'nullable|date',
            'project_code' => 'nullable|string|max:100',
        ]);

        $oldValues = $staff->only(array_keys($validated));

        DB::transaction(function () use ($staff, $validated, $request, $oldValues) {
            $staff->update($validated);
            $staff->syncLinkedUserEmail();

            $this->auditService->log(
                'staff.updated',
                'staff',
                $staff->id,
                $oldValues,
                $staff->fresh()->only(array_keys($validated)),
                'Staff record updated',
                'success',
                $request->user()->id,
                $request
            );
        });

        return response()->json(['data' => $staff->fresh()->load('department', 'campus', 'lineManager')]);
    }

    public function destroy(Request $request, int $id)
    {
        $staff = Staff::findOrFail($id);

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

        return response()->json(null, 204);
    }
}
