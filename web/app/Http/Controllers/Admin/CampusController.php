<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Campus;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CampusController extends Controller
{
    public function __construct(protected AuditService $auditService) {}

    public function index(): View
    {
        $campuses = Campus::query()
            ->with('parentCampus:id,campus_name')
            ->orderBy('campus_name')
            ->get();

        return view('admin.campuses.index', [
            'campuses' => $campuses,
            'parentCampuses' => Campus::query()->orderBy('campus_name')->get(['id', 'campus_name']),
            'campusTypes' => ['main', 'community_college', 'sub_county_hub'],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'campus_code' => ['required', 'string', 'max:20', 'unique:campuses,campus_code'],
            'campus_name' => ['required', 'string', 'max:200'],
            'campus_type' => ['required', 'in:main,community_college,sub_county_hub'],
            'parent_campus_id' => ['nullable', 'exists:campuses,id'],
            'county' => ['nullable', 'string', 'max:100'],
            'sub_county' => ['nullable', 'string', 'max:100'],
            'physical_address' => ['nullable', 'string', 'max:500'],
        ]);

        $campus = Campus::create([
            ...$validated,
            'is_active' => 1,
            'created_by' => $request->user()->id,
        ]);

        $this->auditService->log(
            'core.campus.created',
            'campuses',
            $campus->id,
            null,
            $campus->only(['campus_code', 'campus_name', 'campus_type']),
            null,
            'success',
            $request->user()->id,
            $request
        );

        return back()->with('status', 'Campus created successfully.');
    }

    public function update(Request $request, Campus $campus): RedirectResponse
    {
        $validated = $request->validate([
            'campus_code' => ['required', 'string', 'max:20', 'unique:campuses,campus_code,'.$campus->id],
            'campus_name' => ['required', 'string', 'max:200'],
            'campus_type' => ['required', 'in:main,community_college,sub_county_hub'],
            'parent_campus_id' => ['nullable', 'exists:campuses,id'],
            'county' => ['nullable', 'string', 'max:100'],
            'sub_county' => ['nullable', 'string', 'max:100'],
            'physical_address' => ['nullable', 'string', 'max:500'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $old = $campus->only(['campus_code', 'campus_name', 'campus_type', 'is_active']);
        $campus->update([
            ...$validated,
            'is_active' => $request->boolean('is_active'),
        ]);

        $this->auditService->log(
            'core.campus.updated',
            'campuses',
            $campus->id,
            $old,
            $campus->only(['campus_code', 'campus_name', 'campus_type', 'is_active']),
            null,
            'success',
            $request->user()->id,
            $request
        );

        return back()->with('status', 'Campus updated successfully.');
    }
}
