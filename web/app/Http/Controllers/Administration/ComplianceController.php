<?php

namespace App\Http\Controllers\Administration;

use App\Http\Controllers\Controller;
use App\Models\Administration\InspectionCheck;
use App\Models\Administration\StatutoryCertification;
use App\Services\Administration\AdministrationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class ComplianceController extends Controller
{
    public function __construct(protected AdministrationService $admin) {}

    public function statutory(): View
    {
        $certs = Schema::hasTable('admin_statutory_certifications')
            ? StatutoryCertification::query()->orderBy('authority')->orderBy('title')->paginate(20)
            : collect();

        return view('administration.statutory.index', compact('certs'));
    }

    public function storeStatutory(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:300'],
            'authority' => ['required', 'in:KRA,TVETA,MoE,other'],
            'certificate_number' => ['nullable', 'string', 'max:150'],
            'issued_on' => ['nullable', 'date'],
            'expires_on' => ['nullable', 'date', 'after_or_equal:issued_on'],
            'alignment_notes' => ['nullable', 'string', 'max:3000'],
        ]);

        $cert = StatutoryCertification::query()->create([
            'certificate_code' => $this->admin->nextCode('STC'),
            'title' => $data['title'],
            'authority' => $data['authority'],
            'certificate_number' => $data['certificate_number'] ?? null,
            'issued_on' => $data['issued_on'] ?? null,
            'expires_on' => $data['expires_on'] ?? null,
            'status' => 'active',
            'alignment_notes' => $data['alignment_notes'] ?? null,
            'updated_by' => $request->user()->id,
        ]);
        $cert->refreshStatus();
        $cert->save();

        return back()->with('status', 'Statutory certification recorded.');
    }

    public function inspection(): View
    {
        $readiness = $this->admin->inspectionReadiness();

        return view('administration.inspection.index', [
            'readiness' => $readiness,
            'checks' => Schema::hasTable('admin_inspection_checks')
                ? InspectionCheck::query()->latest()->paginate(20)
                : collect(),
        ]);
    }

    public function storeInspectionCheck(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'area' => ['required', 'string', 'max:100'],
            'requirement' => ['required', 'string', 'max:500'],
            'regulator' => ['nullable', 'in:KRA,TVETA,MoE,other'],
            'status' => ['required', 'in:pending,ready,gap,waived'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        InspectionCheck::query()->create([
            'check_code' => $this->admin->nextCode('INS'),
            ...$data,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        return back()->with('status', 'Inspection checklist item saved.');
    }

    public function updateInspectionStatus(Request $request, InspectionCheck $check): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:pending,ready,gap,waived'],
        ]);

        $check->update([
            'status' => $data['status'],
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        return back()->with('status', 'Inspection item updated.');
    }
}
