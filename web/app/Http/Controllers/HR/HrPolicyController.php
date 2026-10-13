<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\HrPolicy;
use App\Models\PolicyAcknowledgement;
use App\Models\PolicyRead;
use App\Models\Staff;
use App\Services\StoredFileService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Illuminate\Support\Str;

class HrPolicyController extends Controller
{
    public function __construct(
        protected StoredFileService $files,
    ) {}

    public function index(): View
    {
        $policies = HrPolicy::with('uploadedBy')
            ->orderByDesc('created_at')
            ->paginate(25);

        return view('hr.policies.index', ['policies' => $policies]);
    }

    public function create(): View
    {
        $departments = Department::query()
            ->orderBy('dept_name')
            ->get(['id', 'dept_name']);

        $staffList = Staff::query()
            ->excludePlatformOperators()
            ->with('department')
            ->orderBy('surname')
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'surname', 'employee_number', 'job_title', 'department_id']);

        return view('hr.policies.create', [
            'departments' => $departments,
            'staffList' => $staffList,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:200',
            'description' => 'nullable|string|max:2000',
            'file' => 'required|file|max:10240',
            'category' => 'required|string|in:general,leave,conduct,benefits,safety,other',
            'effective_date' => 'nullable|date',
            'expiry_date' => 'nullable|date',
            'tags' => 'nullable|string|max:500',
            'target_type' => 'required|string|in:all,department,staff',
            'target_departments' => 'required_if:target_type,department|nullable|array|min:1',
            'target_departments.*' => 'exists:departments,id',
            'target_staff_ids' => 'required_if:target_type,staff|nullable|array|min:1',
            'target_staff_ids.*' => 'exists:staff,id',
        ]);

        $file = $validated['file'];
        $path = $this->files->store($file, 'hr-policies', 'public', time().'_'.$file->getClientOriginalName());

        $policy = HrPolicy::create([
            'title' => $validated['title'],
            'slug' => Str::slug($validated['title']) . '-' . time(),
            'version' => '1.0',
            'description' => $validated['description'],
            'file_path' => $path,
            'original_filename' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'category' => $validated['category'],
            'effective_date' => $validated['effective_date'],
            'expiry_date' => $validated['expiry_date'],
            'tags' => $validated['tags'],
            'target_type' => $validated['target_type'],
            'target_departments' => $validated['target_type'] === 'department' ? $validated['target_departments'] : null,
            'target_staff_ids' => $validated['target_type'] === 'staff' ? $validated['target_staff_ids'] : null,
            'target_roles' => null,
            'uploaded_by' => $request->user()->staff_id ?? Staff::first()?->id,
        ]);

        // Auto-send to selected audience
        $this->sendPolicyToAudience($policy);

        return redirect()->route('hr.policies.show', $policy)->with('success', 'Policy uploaded and sent successfully.');
    }

    private function sendPolicyToAudience(HrPolicy $policy): int
    {
        $staffIds = $this->recipientStaffIds(
            $policy->target_type,
            $policy->target_staff_ids ?? [],
            $policy->target_departments ?? [],
        );

        return $this->createAcknowledgements($policy, $staffIds);
    }

    /**
     * @param  array<int, int|string>  $staffIds
     * @param  array<int, int|string>  $departmentIds
     * @return list<int>
     */
    private function recipientStaffIds(string $targetType, array $staffIds = [], array $departmentIds = []): array
    {
        $query = Staff::query()->excludePlatformOperators();

        if ($targetType === 'staff') {
            $query->whereIn('id', $staffIds);
        } elseif ($targetType === 'department') {
            $query->whereIn('department_id', $departmentIds);
        } elseif ($targetType !== 'all') {
            return [];
        }

        return $query->distinct()->pluck('id')->map(fn ($id) => (int) $id)->all();
    }

    /**
     * @param  list<int>  $staffIds
     */
    private function createAcknowledgements(HrPolicy $policy, array $staffIds): int
    {
        $policyVersion = trim((string) $policy->version);

        if ($policyVersion === '') {
            $policyVersion = '1.0';
            $policy->update(['version' => $policyVersion]);
        }

        $sent = 0;

        foreach ($staffIds as $staffId) {
            $existing = PolicyAcknowledgement::query()
                ->where('policy_id', $policy->id)
                ->where('staff_id', $staffId)
                ->where('policy_version', $policyVersion)
                ->first();

            if (! $existing) {
                PolicyAcknowledgement::create([
                    'policy_id' => $policy->id,
                    'policy_name' => $policy->title,
                    'policy_version' => $policyVersion,
                    'policy_file_path' => $policy->file_path,
                    'effective_date' => $policy->effective_date ?? now()->toDateString(),
                    'staff_id' => $staffId,
                    'is_acknowledged' => false,
                    'acknowledgement_method' => 'digital',
                ]);
                $sent++;
            }
        }

        return $sent;
    }

    public function show(int $id): View
    {
        $policy = HrPolicy::with('uploadedBy')->findOrFail($id);

        $acknowledgements = $this->acknowledgementsQuery()
            ->where('policy_id', $id)
            ->with(['staff.department', 'staff.user.roles'])
            ->orderByDesc('created_at')
            ->paginate(25);

        return view('hr.policies.show', [
            'policy' => $policy,
            'acknowledgements' => $acknowledgements,
        ]);
    }

    public function edit(int $id): View
    {
        $policy = HrPolicy::findOrFail($id);

        $departments = Department::query()
            ->orderBy('dept_name')
            ->get(['id', 'dept_name']);

        $staffList = Staff::query()
            ->excludePlatformOperators()
            ->with('department')
            ->orderBy('surname')
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'surname', 'employee_number', 'job_title', 'department_id']);

        return view('hr.policies.edit', [
            'policy' => $policy,
            'departments' => $departments,
            'staffList' => $staffList,
        ]);
    }

    public function update(Request $request, int $id)
    {
        $policy = HrPolicy::findOrFail($id);

        $validated = $request->validate([
            'title' => 'required|string|max:200',
            'description' => 'nullable|string|max:2000',
            'file' => 'nullable|file|max:10240',
            'category' => 'required|string|in:general,leave,conduct,benefits,safety,other',
            'effective_date' => 'nullable|date',
            'expiry_date' => 'nullable|date',
            'tags' => 'nullable|string|max:500',
            'is_active' => 'sometimes|boolean',
            'target_type' => 'required|string|in:all,department,staff',
            'target_departments' => 'required_if:target_type,department|nullable|array|min:1',
            'target_departments.*' => 'exists:departments,id',
            'target_staff_ids' => 'required_if:target_type,staff|nullable|array|min:1',
            'target_staff_ids.*' => 'exists:staff,id',
        ]);

        $updateData = [
            'title' => $validated['title'],
            'slug' => Str::slug($validated['title']) . '-' . $policy->id,
            'description' => $validated['description'],
            'category' => $validated['category'],
            'effective_date' => $validated['effective_date'],
            'expiry_date' => $validated['expiry_date'],
            'tags' => $validated['tags'],
            'is_active' => $request->boolean('is_active', $policy->is_active),
            'target_type' => $validated['target_type'],
            'target_departments' => $validated['target_type'] === 'department' ? $validated['target_departments'] : null,
            'target_staff_ids' => $validated['target_type'] === 'staff' ? $validated['target_staff_ids'] : null,
            'target_roles' => null,
        ];

        if ($request->hasFile('file')) {
            $file = $validated['file'];
            $path = $this->files->store($file, 'hr-policies', 'public', time().'_'.$file->getClientOriginalName());

            $updateData['file_path'] = $path;
            $updateData['original_filename'] = $file->getClientOriginalName();
            $updateData['mime_type'] = $file->getMimeType();
            $updateData['file_size'] = $file->getSize();
        }

        $policy->update($updateData);

        return redirect()->route('hr.policies.show', $policy)->with('success', 'Policy updated successfully.');
    }

    public function destroy(Request $request, int $id)
    {
        $policy = HrPolicy::findOrFail($id);
        $policy->delete();

        return redirect()->route('hr.policies.index')->with('success', 'Policy deleted successfully.');
    }

    public function sendForm(int $id): View
    {
        $policy = HrPolicy::findOrFail($id);
        $staffList = Staff::query()
            ->excludePlatformOperators()
            ->orderBy('surname')
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'surname', 'employee_number', 'job_title', 'department_id']);

        $departments = Department::query()
            ->orderBy('dept_name')
            ->get(['id', 'dept_name']);

        return view('hr.policies.send', [
            'policy' => $policy,
            'staffList' => $staffList,
            'departments' => $departments,
        ]);
    }

    public function sendToStaff(Request $request, int $id)
    {
        $policy = HrPolicy::findOrFail($id);

        $validated = $request->validate([
            'target_type' => 'required|string|in:all,staff,department',
            'staff_ids' => 'required_if:target_type,staff|nullable|array|min:1',
            'staff_ids.*' => 'exists:staff,id',
            'target_departments' => 'required_if:target_type,department|nullable|array|min:1',
            'target_departments.*' => 'exists:departments,id',
        ]);

        $targetType = $validated['target_type'];
        $staffIds = $this->recipientStaffIds(
            $targetType,
            $validated['staff_ids'] ?? [],
            $validated['target_departments'] ?? [],
        );

        abort_if($staffIds === [], 422, 'No eligible staff members were found for the selected audience.');

        $sent = DB::transaction(function () use ($policy, $targetType, $validated, $staffIds): int {
            $policy->update([
                'target_type' => $targetType,
                'target_staff_ids' => $targetType === 'staff' ? $validated['staff_ids'] : null,
                'target_departments' => $targetType === 'department' ? $validated['target_departments'] : null,
                'target_roles' => null,
            ]);

            return $this->createAcknowledgements($policy, $staffIds);
        });

        return redirect()->route('hr.policies.show', $policy)->with('success', "Policy sent to {$sent} staff member(s).");
    }

    public function acknowledgementsIndex(Request $request): View
    {
        $query = $this->acknowledgementsQuery()
            ->with(['policy', 'staff'])
            ->when($request->filled('status'), function ($q) use ($request) {
                $q->where('is_acknowledged', $request->boolean('status'));
            })
            ->orderByDesc('created_at');

        $acknowledgements = $query->paginate(25);

        return view('hr.policies.acknowledgements', [
            'acknowledgements' => $acknowledgements,
        ]);
    }

    public function acknowledgements(int $id): View
    {
        $policy = HrPolicy::findOrFail($id);
        $acknowledgements = $this->acknowledgementsQuery()
            ->where('policy_id', $id)
            ->with(['staff.department', 'staff.user.roles'])
            ->orderByDesc('created_at')
            ->paginate(25);

        return view('hr.policies.acknowledgements', [
            'policy' => $policy,
            'acknowledgements' => $acknowledgements,
        ]);
    }

    private function acknowledgementsQuery()
    {
        return PolicyAcknowledgement::query()
            ->select('policy_acknowledgements.*')
            ->addSelect([
                'read_at' => PolicyRead::query()
                    ->select('read_at')
                    ->whereColumn('policy_reads.policy_id', 'policy_acknowledgements.policy_id')
                    ->whereColumn('policy_reads.staff_id', 'policy_acknowledgements.staff_id')
                    ->limit(1),
            ])
            ->withCasts(['read_at' => 'datetime']);
    }

    public function assigned(Request $request)
    {
        $staff = app(\App\Services\EmployeePortalService::class)->staffForUser($request->user());
        abort_if(! $staff, 404);

        $acknowledgements = PolicyAcknowledgement::query()
            ->where('staff_id', $staff->id)
            ->with('policy')
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('employee.policies.assigned', [
            'staff' => $staff,
            'acknowledgements' => $acknowledgements,
            'portalTitle' => 'HR Policies',
            'hideTeachingCard' => true,
        ]);
    }

    public function acknowledgeForm(Request $request, int $id)
    {
        $staff = app(\App\Services\EmployeePortalService::class)->staffForUser($request->user());
        abort_if(! $staff, 404);

        $acknowledgement = PolicyAcknowledgement::query()
            ->where('policy_id', $id)
            ->where('staff_id', $staff->id)
            ->firstOrFail();

        abort_if($acknowledgement->is_acknowledged, 403, 'You have already acknowledged this policy.');

        $policy = HrPolicy::findOrFail($id);

        return view('employee.policies.acknowledge', [
            'staff' => $staff,
            'acknowledgement' => $acknowledgement,
            'policy' => $policy,
            'hideTeachingCard' => true,
        ]);
    }

    public function acknowledge(Request $request, int $id)
    {
        $staff = app(\App\Services\EmployeePortalService::class)->staffForUser($request->user());
        abort_if(! $staff, 404);

        $validated = $request->validate([
            'full_name' => 'required|string|max:200',
            'employee_number' => 'required|string|max:50',
            'signature' => 'nullable|string|max:2000',
            'read_confirmed' => 'required|accepted',
        ]);

        DB::transaction(function () use ($staff, $id, $validated, $request): void {
            $acknowledgement = PolicyAcknowledgement::query()
                ->where('policy_id', $id)
                ->where('staff_id', $staff->id)
                ->lockForUpdate()
                ->firstOrFail();

            abort_if($acknowledgement->is_acknowledged, 403, 'You have already acknowledged this policy.');

            $acknowledgedAt = now();
            $userAgent = $request->userAgent();

            $acknowledgement->update([
                'is_acknowledged' => true,
                'acknowledged_at' => $acknowledgedAt,
                'acknowledged_by' => $validated['full_name'],
                'employee_number' => $validated['employee_number'],
                'signature' => $validated['signature'],
            ]);

            PolicyRead::query()->firstOrCreate([
                'policy_id' => $id,
                'staff_id' => $staff->id,
            ], [
                'read_at' => $acknowledgedAt,
                'ip_address' => $request->ip(),
                'user_agent' => $userAgent ? substr($userAgent, 0, 500) : null,
            ]);
        });

        return redirect()->route('policies.assigned')->with('success', 'Policy acknowledged successfully.');
    }

    public function download(int $id)
    {
        $policy = HrPolicy::findOrFail($id);
        $routeName = request()->route()->getName();

        if (in_array($routeName, ['policies.download'], true) && ! $policy->is_active) {
            abort(404);
        }

        if (! $policy->file_path || ! Storage::disk('public')->exists($policy->file_path)) {
            abort(404);
        }

        return Storage::disk('public')->download($policy->file_path, $policy->original_filename);
    }

    public function view(int $id)
    {
        $policy = HrPolicy::findOrFail($id);
        $routeName = request()->route()->getName();

        if (in_array($routeName, ['policies.view'], true) && ! $policy->is_active) {
            abort(404);
        }

        if (! $policy->file_path || ! Storage::disk('public')->exists($policy->file_path)) {
            abort(404);
        }

        // Track read event for employee portal
        $staff = app(\App\Services\EmployeePortalService::class)->staffForUser(request()->user());
        if ($staff) {
            PolicyRead::firstOrCreate([
                'policy_id' => $policy->id,
                'staff_id' => $staff->id,
            ], [
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        }

        return Storage::disk('public')->response($policy->file_path, $policy->original_filename);
    }
}
