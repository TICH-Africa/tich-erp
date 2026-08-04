<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\HrPolicy;
use App\Models\Staff;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Illuminate\Support\Str;

class HrPolicyController extends Controller
{
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
        $path = $file->storeAs('hr-policies', time() . '_' . $file->getClientOriginalName(), 'public');

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
            if ($policy->file_path && Storage::disk('public')->exists($policy->file_path)) {
                Storage::disk('public')->delete($policy->file_path);
            }

            $file = $validated['file'];
            $path = $file->storeAs('hr-policies', time() . '_' . $file->getClientOriginalName(), 'public');

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

        if ($policy->file_path && Storage::disk('public')->exists($policy->file_path)) {
            Storage::disk('public')->delete($policy->file_path);
        }

        $policy->delete();

        return redirect()->route('hr.policies.index')->with('success', 'Policy deleted successfully.');
    }

    public function download(int $id)
    {
        $policy = HrPolicy::findOrFail($id);
        $routeName = request()->route()->getName();

        if ($routeName === 'staff.policies.download' && ! $policy->is_active) {
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

        if ($routeName === 'staff.policies.view' && ! $policy->is_active) {
            abort(404);
        }

        if (! $policy->file_path || ! Storage::disk('public')->exists($policy->file_path)) {
            abort(404);
        }

        return Storage::disk('public')->response($policy->file_path, $policy->original_filename);
    }
}
