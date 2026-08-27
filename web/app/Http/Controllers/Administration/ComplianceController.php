<?php

namespace App\Http\Controllers\Administration;

use App\Http\Controllers\Controller;
use App\Models\Administration\InspectionCheck;
use App\Models\Administration\StatutoryCertification;
use App\Services\Administration\AdministrationService;
use App\Services\StoredFileService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ComplianceController extends Controller
{
    public function __construct(
        protected AdministrationService $admin,
        protected StoredFileService $files,
    ) {}

    public function statutory(): View
    {
        $certs = Schema::hasTable('admin_statutory_certifications')
            ? StatutoryCertification::query()->orderBy('authority')->orderBy('title')->paginate(20)
            : collect();

        $readiness = Schema::hasTable('admin_statutory_certifications')
            ? $this->admin->statutoryReadiness()
            : [
                'score' => 100,
                'ready' => 0,
                'gaps' => 0,
                'certs_expiring' => 0,
                'certifications' => collect(),
            ];

        return view('administration.statutory.index', compact('certs', 'readiness'));
    }

    public function storeStatutory(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:300'],
            'authority' => ['required', 'string', 'max:255'],
            'certificate_number' => ['nullable', 'string', 'max:150'],
            'issued_on' => ['nullable', 'date'],
            'expires_on' => ['nullable', 'date', 'after_or_equal:issued_on'],
            'alignment_notes' => ['nullable', 'string', 'max:3000'],
            'certificate_file' => ['nullable', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,webp'],
        ]);

        $documentPath = null;
        if ($request->hasFile('certificate_file')) {
            $file = $request->file('certificate_file');
            $documentPath = $this->files->store(
                $file,
                'administration/statutory',
                'public',
                time().'_'.$file->getClientOriginalName()
            );
        }

        $cert = StatutoryCertification::query()->create([
            'certificate_code' => $this->admin->nextCode('STC'),
            'title' => $data['title'],
            'authority' => trim($data['authority']),
            'certificate_number' => $data['certificate_number'] ?? null,
            'issued_on' => $data['issued_on'] ?? null,
            'expires_on' => $data['expires_on'] ?? null,
            'status' => 'active',
            'document_path' => $documentPath,
            'alignment_notes' => $data['alignment_notes'] ?? null,
            'updated_by' => $request->user()->id,
        ]);
        $cert->refreshStatus();
        $cert->save();

        return back()->with('status', 'Statutory certification recorded.');
    }

    public function downloadStatutory(StatutoryCertification $certification): StreamedResponse
    {
        abort_unless(filled($certification->document_path), 404);

        $relative = $this->files->relativePath($certification->document_path);
        abort_unless($relative && Storage::disk('public')->exists($relative), 404);

        $filename = basename($relative);

        return Storage::disk('public')->download($relative, $filename);
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
