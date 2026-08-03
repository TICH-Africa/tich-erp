<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\JobVacancy;
use App\Models\RecruitmentApplication;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class VacancyApplicationController extends Controller
{
    public function create(int $vacancy): View
    {
        $vacancy = JobVacancy::findOrFail($vacancy);

        return view('vacancies.apply', ['vacancy' => $vacancy]);
    }

    public function store(Request $request, int $vacancy)
    {
        $vacancy = JobVacancy::findOrFail($vacancy);

        $validated = $request->validate([
            'full_name' => 'required|string|max:300',
            'id_number' => 'required|string|max:50',
            'date_of_birth' => 'required|date',
            'gender' => 'required|string|in:Male,Female,Other',
            'marital_status' => 'nullable|string|in:Single,Married,Divorced,Widowed,Separated',
            'email' => 'required|email|max:255',
            'phone_number' => 'required|string|max:30',
            'postal_address' => 'nullable|string|max:300',
            'physical_address' => 'nullable|string|max:500',
            'highest_qualification' => 'required|string|in:KCSE,Diploma,Certificate,Bachelors,Masters,PhD,Professional,Other',
            'institution' => 'required|string|max:300',
            'year_completed' => 'required|integer|min:1950|max:' . (date('Y') + 1),
            'grade' => 'nullable|string|max:50',
            'years_of_experience' => 'required|integer|min:0|max:50',
            'current_organization' => 'nullable|string|max:300',
            'area_of_specialization' => 'nullable|string|max:300',
            'cv' => 'required|file|max:10240|mimes:pdf,doc,docx',
            'cover_letter' => 'nullable|file|max:10240|mimes:pdf,doc,docx',
            'certificates' => 'nullable|array|max:5',
            'certificates.*' => 'file|max:10240|mimes:pdf,doc,docx,jpg,jpeg,png',
            'referee1_name' => 'required|string|max:300',
            'referee1_title' => 'required|string|max:200',
            'referee1_organization' => 'required|string|max:300',
            'referee1_contact' => 'required|string|max:100',
            'referee2_name' => 'required|string|max:300',
            'referee2_title' => 'required|string|max:200',
            'referee2_organization' => 'required|string|max:300',
            'referee2_contact' => 'required|string|max:100',
            'expected_salary' => 'nullable|string|max:100',
            'notice_period' => 'nullable|string|in:1 week,2 weeks,3 weeks,4 weeks,5 weeks,6 weeks,8 weeks,12 weeks,Immediate',
            'declaration' => 'required|accepted',
        ]);

        $applicationNumber = 'APP-' . date('Y') . '-' . str_pad((string) RecruitmentApplication::count() + 1, 5, '0', STR_PAD_LEFT);

        $cvPath = $request->file('cv')->storeAs("applications/{$applicationNumber}", 'cv.' . $request->file('cv')->getClientOriginalExtension(), 'public');

        $coverLetterPath = null;
        if ($request->hasFile('cover_letter')) {
            $coverLetterPath = $request->file('cover_letter')->storeAs("applications/{$applicationNumber}", 'cover_letter.' . $request->file('cover_letter')->getClientOriginalExtension(), 'public');
        }

        $certificatePaths = [];
        if ($request->hasFile('certificates')) {
            foreach ($request->file('certificates') as $index => $file) {
                $certificatePaths[] = $file->storeAs("applications/{$applicationNumber}", "certificate_{$index}." . $file->getClientOriginalExtension(), 'public');
            }
        }

        $application = RecruitmentApplication::create([
            'application_number' => $applicationNumber,
            'vacancy_id' => $vacancy->id,
            'full_name' => $validated['full_name'],
            'id_number' => $validated['id_number'],
            'date_of_birth' => $validated['date_of_birth'],
            'gender' => $validated['gender'],
            'marital_status' => $validated['marital_status'],
            'email' => $validated['email'],
            'phone_number' => $validated['phone_number'],
            'postal_address' => $validated['postal_address'],
            'physical_address' => $validated['physical_address'],
            'highest_qualification' => $validated['highest_qualification'],
            'institution' => $validated['institution'],
            'year_completed' => $validated['year_completed'],
            'grade' => $validated['grade'],
            'years_of_experience' => $validated['years_of_experience'],
            'current_organization' => $validated['current_organization'],
            'area_of_specialization' => $validated['area_of_specialization'],
            'cv_file_path' => $cvPath,
            'cover_letter_file_path' => $coverLetterPath,
            'certificates_file_paths' => $certificatePaths,
            'referee1_name' => $validated['referee1_name'],
            'referee1_title' => $validated['referee1_title'],
            'referee1_organization' => $validated['referee1_organization'],
            'referee1_contact' => $validated['referee1_contact'],
            'referee2_name' => $validated['referee2_name'],
            'referee2_title' => $validated['referee2_title'],
            'referee2_organization' => $validated['referee2_organization'],
            'referee2_contact' => $validated['referee2_contact'],
            'expected_salary' => $validated['expected_salary'],
            'notice_period' => $validated['notice_period'],
            'status' => 'submitted',
            'shortlist_status' => 'pending',
            'application_source' => 'portal',
        ]);

        // Send email notification
        try {
            \Illuminate\Support\Facades\Mail::to($application->email)->send(new \App\Mail\VacancyApplicationReceived($application, $vacancy));
        } catch (\Exception $e) {
            // Log error but don't fail the application
            \Log::error('Failed to send application confirmation email: ' . $e->getMessage());
        }

        return redirect()->route('vacancies.apply.confirmation', ['application' => $application->id])
            ->with('success', 'Application submitted successfully!');
    }

    public function confirmation(int $application): View
    {
        $application = RecruitmentApplication::with('vacancy')->findOrFail($application);

        return view('vacancies.confirmation', ['application' => $application]);
    }

    public function track(Request $request): View
    {
        $applicant = null;

        if ($request->isMethod('post')) {
            $validated = $request->validate([
                'application_number' => 'required|string|max:50',
                'email' => 'required|email|max:255',
            ]);

            $applicant = RecruitmentApplication::with('vacancy')
                ->where('application_number', $validated['application_number'])
                ->where('email', $validated['email'])
                ->first();
        }

        return view('vacancies.track', [
            'applicant' => $applicant,
            'applicationNumber' => $request->application_number ?? null,
            'email' => $request->email ?? null,
        ]);
    }
}
