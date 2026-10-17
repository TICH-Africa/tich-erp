<?php

namespace App\Http\Controllers\Administration;

use App\Http\Controllers\Controller;
use App\Models\AcademicProgram;
use App\Models\Applicant;
use App\Models\Student;
use Illuminate\Database\Eloquent\Builder;
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

    public function applications(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $programId = (int) ($request->query('program_id', 0) ?: 0);

        $programs = Schema::hasTable('academic_programs')
            ? AcademicProgram::query()->where('status', 'active')->orderBy('program_name')->get(['id', 'program_code', 'program_name'])
            : collect();

        $applications = collect();
        if (Schema::hasTable('applicants')) {
            $applicationQuery = Applicant::query()->with(['program']);

            if ($search !== '') {
                $applicationQuery->where(function (Builder $q) use ($search) {
                    $q->where('application_number', 'like', "%{$search}%")
                        ->orWhere('first_name', 'like', "%{$search}%")
                        ->orWhere('surname', 'like', "%{$search}%")
                        ->orWhereHas('program', function (Builder $pq) use ($search) {
                            $pq->where('program_name', 'like', "%{$search}%")
                                ->orWhere('program_code', 'like', "%{$search}%");
                        });
                });
            }

            if ($programId > 0) {
                $applicationQuery->where('program_id', $programId);
            }

            $applications = $applicationQuery->latest('id')->paginate(20)->withQueryString();
        }

        $existingStudents = collect();
        if (Schema::hasTable('students')) {
            $studentQuery = Student::query()
                ->with(['program', 'campus'])
                ->where('is_active', 1);

            if ($search !== '') {
                $studentQuery->where(function (Builder $q) use ($search) {
                    $q->where('registration_number', 'like', "%{$search}%")
                        ->orWhere('first_name', 'like', "%{$search}%")
                        ->orWhere('surname', 'like', "%{$search}%")
                        ->orWhereHas('program', function (Builder $pq) use ($search) {
                            $pq->where('program_name', 'like', "%{$search}%")
                                ->orWhere('program_code', 'like', "%{$search}%");
                        })
                        ->orWhereHas('applicant', function (Builder $aq) use ($search) {
                            $aq->where('first_name', 'like', "%{$search}%")
                                ->orWhere('surname', 'like', "%{$search}%")
                                ->orWhere('application_number', 'like', "%{$search}%");
                        });
                });
            }

            if ($programId > 0) {
                $studentQuery->where('program_id', $programId);
            }

            $existingStudents = $studentQuery->latest('id')->paginate(15)->withQueryString();
        }

        return view('administration.applications.index', [
            'applications' => $applications,
            'existingStudents' => $existingStudents,
            'applyUrl' => route('apply.index'),
            'search' => $search,
            'programId' => $programId,
            'programs' => $programs,
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
