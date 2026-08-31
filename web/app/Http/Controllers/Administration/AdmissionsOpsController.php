<?php

namespace App\Http\Controllers\Administration;

use App\Http\Controllers\Controller;
use App\Models\Applicant;
use App\Models\Student;
use App\Services\Administration\AdministrationService;
use App\Services\Administration\AdmissionLetterTemplateService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdmissionsOpsController extends Controller
{
    public function __construct(
        protected AdministrationService $admin,
        protected AdmissionLetterTemplateService $admissionLetters,
    ) {}

    public function applications(): View
    {
        $applications = Schema::hasTable('applicants')
            ? Applicant::query()->with(['program'])->latest('id')->paginate(20)
            : collect();

        return view('administration.applications.index', [
            'applications' => $applications,
            'applyUrl' => route('apply.index'),
        ]);
    }

    public function lifecycle(): View
    {
        return view('administration.lifecycle.index', [
            'stats' => $this->admin->admissionsLifecycleStats(),
            'admissionsUrl' => route('administration.applications.index'),
        ]);
    }

    public function packages(Request $request): View
    {
        $students = Schema::hasTable('students')
            ? Student::query()
                ->with(['program', 'applicant'])
                ->where('is_active', 1)
                ->latest('id')
                ->paginate(20)
            : collect();

        return view('administration.admission-packages.index', [
            'students' => $students,
            'admissionsUrl' => route('administration.applications.index', ['status' => 'admitted']),
            'letterExists' => $this->admissionLetters->exists(),
            'letterFilename' => $this->admissionLetters->originalFilename(),
        ]);
    }

    public function storeAdmissionLetter(Request $request): RedirectResponse
    {
        $request->validate([
            'admission_letter' => ['required', 'file', 'max:10240', 'mimes:pdf,doc,docx,jpg,jpeg,png,webp'],
        ]);

        $this->admissionLetters->store($request->file('admission_letter'), $request->user()->id);

        return back()->with('status', 'Admission letter uploaded. It will be attached when an application is admitted and confirmation is emailed.');
    }

    public function downloadAdmissionLetter(): StreamedResponse
    {
        $attachment = $this->admissionLetters->attachment();
        abort_unless($attachment, 404);

        return Storage::disk('public')->download($attachment['path'], $attachment['filename']);
    }

    public function destroyAdmissionLetter(Request $request): RedirectResponse
    {
        $this->admissionLetters->clear($request->user()->id);

        return back()->with('status', 'Admission letter template removed.');
    }
}
