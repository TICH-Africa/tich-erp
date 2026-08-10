<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\HrPolicy;
use App\Models\PolicyAcknowledgement;
use App\Models\Staff;
use App\Services\StoredFileService;
use Illuminate\Http\Request;
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
        return view('hr.policies.create');
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
        ]);

        $file = $validated['file'];
        $path = $this->files->store($file, 'hr-policies', 'public', time().'_'.$file->getClientOriginalName());

        $policy = HrPolicy::create([
            'title' => $validated['title'],
            'slug' => Str::slug($validated['title']) . '-' . time(),
            'description' => $validated['description'],
            'file_path' => $path,
            'original_filename' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'category' => $validated['category'],
            'effective_date' => $validated['effective_date'],
            'expiry_date' => $validated['expiry_date'],
            'tags' => $validated['tags'],
            'uploaded_by' => $request->user()->staff_id ?? Staff::first()?->id,
        ]);

        return redirect()->route('hr.policies.show', $policy)->with('success', 'Policy uploaded successfully.');
    }

    public function show(int $id): View
    {
        $policy = HrPolicy::with('uploadedBy')->findOrFail($id);

        return view('hr.policies.show', ['policy' => $policy]);
    }

    public function edit(int $id): View
    {
        $policy = HrPolicy::findOrFail($id);

        return view('hr.policies.edit', ['policy' => $policy]);
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
            ->orderBy('surname')
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'surname', 'employee_number', 'job_title', 'department_id']);

        return view('hr.policies.send', [
            'policy' => $policy,
            'staffList' => $staffList,
        ]);
    }

    public function sendToStaff(Request $request, int $id)
    {
        $policy = HrPolicy::findOrFail($id);

        $validated = $request->validate([
            'staff_ids' => 'required|array|min:1',
            'staff_ids.*' => 'exists:staff,id',
            'send_to_all' => 'sometimes|boolean',
        ]);

        if (! empty($validated['send_to_all'])) {
            $staffIds = Staff::query()->pluck('id')->toArray();
        } else {
            $staffIds = $validated['staff_ids'];
        }

        $sent = 0;
        foreach ($staffIds as $staffId) {
            $existing = PolicyAcknowledgement::query()
                ->where('policy_id', $policy->id)
                ->where('staff_id', $staffId)
                ->where('policy_version', $policy->version)
                ->first();

            if (! $existing) {
                PolicyAcknowledgement::create([
                    'policy_id' => $policy->id,
                    'policy_name' => $policy->title,
                    'policy_version' => $policy->version,
                    'policy_file_path' => $policy->file_path,
                    'effective_date' => $policy->effective_date,
                    'staff_id' => $staffId,
                    'is_acknowledged' => false,
                    'acknowledgement_method' => 'digital',
                ]);
                $sent++;
            }
        }

        return redirect()->route('hr.policies.show', $policy)->with('success', "Policy sent to {$sent} staff member(s).");
    }

    public function acknowledgementsIndex(Request $request): View
    {
        $query = PolicyAcknowledgement::query()
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
        $acknowledgements = PolicyAcknowledgement::query()
            ->where('policy_id', $id)
            ->with('staff')
            ->orderByDesc('created_at')
            ->paginate(25);

        return view('hr.policies.acknowledgements', [
            'policy' => $policy,
            'acknowledgements' => $acknowledgements,
        ]);
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
        ]);
    }

    public function acknowledge(Request $request, int $id)
    {
        $staff = app(\App\Services\EmployeePortalService::class)->staffForUser($request->user());
        abort_if(! $staff, 404);

        $acknowledgement = PolicyAcknowledgement::query()
            ->where('policy_id', $id)
            ->where('staff_id', $staff->id)
            ->firstOrFail();

        abort_if($acknowledgement->is_acknowledged, 403, 'You have already acknowledged this policy.');

        $validated = $request->validate([
            'full_name' => 'required|string|max:200',
            'employee_number' => 'required|string|max:50',
            'signature' => 'nullable|string|max:2000',
        ]);

        $acknowledgement->update([
            'is_acknowledged' => true,
            'acknowledged_at' => now(),
            'acknowledged_by' => $validated['full_name'],
            'employee_number' => $validated['employee_number'],
            'signature' => $validated['signature'],
        ]);

        return redirect()->route('policies.assigned')->with('success', 'Policy acknowledged successfully.');
    }

    public function download(int $id)
    {
        $policy = HrPolicy::findOrFail($id);
        $routeName = request()->route()->getName();

        if (in_array($routeName, ['staff.policies.download', 'policies.download'], true) && ! $policy->is_active) {
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

        if (in_array($routeName, ['staff.policies.view', 'policies.view'], true) && ! $policy->is_active) {
            abort(404);
        }

        if (! $policy->file_path || ! Storage::disk('public')->exists($policy->file_path)) {
            abort(404);
        }

        return Storage::disk('public')->response($policy->file_path, $policy->original_filename);
    }
}
