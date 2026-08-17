<?php

namespace App\Http\Controllers\Administration;

use App\Http\Controllers\Controller;
use App\Models\Applicant;
use App\Models\Student;
use App\Services\Administration\AdministrationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class AdmissionsOpsController extends Controller
{
    public function __construct(protected AdministrationService $admin) {}

    public function applications(): View
    {
        $applications = Schema::hasTable('applicants')
            ? Applicant::query()->with(['program'])->latest('id')->paginate(20)
            : collect();

        return view('administration.applications.index', [
            'applications' => $applications,
            'applyUrl' => route('apply.index'),
            'admissionsUrl' => route('admissions.applications.index'),
        ]);
    }

    public function lifecycle(): View
    {
        return view('administration.lifecycle.index', [
            'stats' => $this->admin->admissionsLifecycleStats(),
            'admissionsUrl' => route('admissions.dashboard'),
            'mpesaUrl' => route('finance.mpesa.settings'),
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
            'admissionsUrl' => route('admissions.applications.index', ['status' => 'approved']),
        ]);
    }
}
