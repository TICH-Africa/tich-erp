<?php

namespace App\Http\Controllers\Administration;

use App\Http\Controllers\Controller;
use App\Models\AcademicProgram;
use App\Models\Campus;
use App\Models\Student;
use App\Models\StudentNextOfKin;
use App\Services\AcademicRecordService;
use App\Services\ProgramsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ExistingStudentController extends Controller
{
    public function __construct(
        protected AcademicRecordService $academicService,
        protected \App\Services\Finance\StudentAccountService $accountService,
        protected ProgramsService $programsService,
    ) {}

    public function create(): View
    {
        $programs = AcademicProgram::where('status', 'active')->orderBy('program_name')->get();
        $campusSelectionOptions = $this->programsService->getCampusSelectionOptions();

        // The form is shared with edit(), so the student must always be defined.
        return view('administration.applications.existing-student', [
            'programs' => $programs,
            'campusSelectionOptions' => $campusSelectionOptions,
            'student' => null,
            'nextOfKin' => collect(),
        ]);
    }

    public function index(Request $request): View
    {
        $query = Student::with(['program', 'campus'])
            ->where('is_active', 1)
            ->orderByDesc('created_at');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('registration_number', 'like', "%{$search}%")
                  ->orWhere('first_name', 'like', "%{$search}%")
                  ->orWhere('surname', 'like', "%{$search}%");
            });
        }

        if ($request->filled('cohort_intake')) {
            $query->where('cohort_intake', $request->cohort_intake);
        }

        if ($request->filled('program_id')) {
            $query->where('program_id', $request->program_id);
        }

        $students = $query->paginate(25);

        $programs = AcademicProgram::where('status', 'active')->orderBy('program_name')->get();
        $cohorts = Student::where('is_active', 1)->distinct()->pluck('cohort_intake')->sortDesc()->values();

        return view('administration.applications.existing-student-index', compact('students', 'programs', 'cohorts'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'registration_number' => 'required|string|max:50|unique:students,registration_number',
            'first_name' => 'required|string|max:100',
            'middle_name' => 'nullable|string|max:100',
            'surname' => 'required|string|max:100',
            'email' => 'required|email|unique:users,email',
            'phone_number' => 'nullable|string|max:20',
            'next_of_kin' => 'nullable|array',
            'next_of_kin.*.full_name' => 'nullable|string|max:300',
            'next_of_kin.*.relationship' => 'nullable|string|max:100',
            'next_of_kin.*.phone_number' => 'nullable|string|max:30',
            'next_of_kin.*.alt_phone' => 'nullable|string|max:30',
            'next_of_kin.*.email' => 'nullable|email|max:255',
            'next_of_kin.*.physical_address' => 'nullable|string|max:500',
            'program_id' => 'required|exists:academic_programs,id',
            'year_joined' => 'required|date',
            'current_year' => 'required|integer|min:1|max:4',
            'program_type' => 'required|in:diploma,certificate',
            'duration_months' => 'required|integer|min:1',
            'semester' => 'required|in:first,second,third',
            'campus_selection_type' => 'required|in:campus,community_college,online',
            'campus_id' => 'nullable|exists:campuses,id',
            'community_college_county' => 'nullable|string|max:100',
            'community_college_site_id' => 'nullable|exists:campuses,id',
            'photo' => 'nullable|image|max:2048',
            'notes' => 'nullable|string',
        ]);

        $nextOfKin = $this->nextOfKinContacts($validated);
        $primary = $nextOfKin[0] ?? [];

        $selectionType = $validated['campus_selection_type'];
        $normalCampusId = $validated['campus_id'] ?? null;
        $siteId = $validated['community_college_site_id'] ?? null;

        if ($selectionType === 'campus') {
            if (! $normalCampusId) {
                throw ValidationException::withMessages([
                    'campus_id' => 'Please select an available campus.',
                ]);
            }

            $campus = Campus::query()
                ->whereKey($normalCampusId)
                ->where('is_active', 1)
                ->first();

            if (! $campus || $campus->campus_type === 'community_college') {
                throw ValidationException::withMessages([
                    'campus_id' => 'Please select a valid campus.',
                ]);
            }

            $validated['campus_id'] = (int) $normalCampusId;
        } elseif ($selectionType === 'community_college') {
            if (! $siteId || ! $validated['community_college_county']) {
                throw ValidationException::withMessages([
                    'community_college_site_id' => 'Please select a county and community college site.',
                ]);
            }

            $campus = Campus::query()
                ->whereKey($siteId)
                ->where('is_active', 1)
                ->where('campus_type', 'community_college')
                ->first();

            if (! $campus || $campus->county !== $validated['community_college_county']) {
                throw ValidationException::withMessages([
                    'community_college_county' => 'Please select a site from the selected county.',
                ]);
            }

            $validated['campus_id'] = (int) $siteId;
        } elseif ($selectionType === 'online') {
            $validated['campus_id'] = null;
        } else {
            throw ValidationException::withMessages([
                'campus_selection_type' => 'Please choose a campus or community college site.',
            ]);
        }

        $yearJoined = \Carbon\Carbon::parse($validated['year_joined']);

        $student = Student::create([
            'registration_number' => $validated['registration_number'],
            'program_id' => $validated['program_id'],
            'application_id' => null,
            'first_name' => $validated['first_name'],
            'middle_name' => $validated['middle_name'],
            'surname' => $validated['surname'],
            'cohort_intake' => $yearJoined->format('Y'),
            'current_year' => $validated['current_year'],
            'enrollment_campus_id' => $validated['campus_id'],
            'enrollment_status' => 'active',
            'entry_pathway' => $validated['program_type'],
            'date_of_admission' => $yearJoined,
            'is_active' => 1,
            'created_by' => auth()->id(),
            'emergency_contact_name' => $primary['full_name'] ?? null,
            'emergency_contact_phone' => $primary['phone_number'] ?? null,
            'emergency_contact_relationship' => $primary['relationship'] ?? null,
            'email' => $validated['email'] ?? null,
            'phone_number' => $validated['phone_number'] ?? null,
        ]);

        $this->syncNextOfKin($student, $nextOfKin);

        if ($request->hasFile('photo')) {
            $student->photo_path = $this->storeStudentPhoto($student, $request->file('photo'));
            $student->save();
        }

        $this->academicService->create($student, [
            'program_id' => $validated['program_id'],
            'enrollment_date' => $yearJoined,
            'status' => 'active',
            'entry_pathway' => $validated['program_type'],
            'notes' => $validated['notes'] ?? null,
        ]);

        $this->accountService->ensureAccount($student);

        return redirect()
            ->route('administration.applications.existing-student.show', $student->id)
            ->with('success', "Student {$student->registration_number} added successfully.");
    }

    public function show(Student $student): View
    {
        $academicRecords = $student->academicRecords()->with('program', 'semester')->get();
        $nextOfKin = $this->nextOfKinRows($student);

        return view('administration.applications.existing-student-show', compact('student', 'academicRecords', 'nextOfKin'));
    }

    public function edit(Student $student): View
    {
        $programs = AcademicProgram::where('status', 'active')->orderBy('program_name')->get();
        $campusSelectionOptions = $this->programsService->getCampusSelectionOptions();

        return view('administration.applications.existing-student', [
            'programs' => $programs,
            'campusSelectionOptions' => $campusSelectionOptions,
            'student' => $student,
            'nextOfKin' => $this->nextOfKinRows($student),
        ]);
    }

    public function update(Request $request, Student $student): RedirectResponse
    {
        $validated = $request->validate([
            'registration_number' => 'required|string|max:50|unique:students,registration_number,'.$student->id,
            'first_name' => 'required|string|max:100',
            'middle_name' => 'nullable|string|max:100',
            'surname' => 'required|string|max:100',
            'email' => [
                'nullable',
                'email',
                Rule::unique('users', 'email')->ignore($student->user_id),
            ],
            'phone_number' => 'nullable|string|max:20',
            'next_of_kin' => 'nullable|array',
            'next_of_kin.*.full_name' => 'nullable|string|max:300',
            'next_of_kin.*.relationship' => 'nullable|string|max:100',
            'next_of_kin.*.phone_number' => 'nullable|string|max:30',
            'next_of_kin.*.alt_phone' => 'nullable|string|max:30',
            'next_of_kin.*.email' => 'nullable|email|max:255',
            'next_of_kin.*.physical_address' => 'nullable|string|max:500',
            'program_id' => 'required|exists:academic_programs,id',
            'year_joined' => 'required|date',
            'current_year' => 'required|integer|min:1|max:4',
            'program_type' => 'required|in:diploma,certificate',
            'duration_months' => 'required|integer|min:1',
            'semester' => 'required|in:first,second,third',
            'campus_selection_type' => 'required|in:campus,community_college,online',
            'campus_id' => 'nullable|exists:campuses,id',
            'community_college_county' => 'nullable|string|max:100',
             'community_college_site_id' => 'nullable|exists:campuses,id',
            'photo' => 'nullable|image|max:2048',
            'photo_remove' => 'boolean',
            'notes' => 'nullable|string',
        ]);

        $nextOfKin = $this->nextOfKinContacts($validated);
        $primary = $nextOfKin[0] ?? [];

        $selectionType = $validated['campus_selection_type'];
        $normalCampusId = $validated['campus_id'] ?? null;
        $siteId = $validated['community_college_site_id'] ?? null;

        if ($selectionType === 'campus') {
            if (! $normalCampusId) {
                throw ValidationException::withMessages([
                    'campus_id' => 'Please select an available campus.',
                ]);
            }

            $campus = Campus::query()
                ->whereKey($normalCampusId)
                ->where('is_active', 1)
                ->first();

            if (! $campus || $campus->campus_type === 'community_college') {
                throw ValidationException::withMessages([
                    'campus_id' => 'Please select a valid campus.',
                ]);
            }

            $validated['campus_id'] = (int) $normalCampusId;
        } elseif ($selectionType === 'community_college') {
            if (! $siteId || ! $validated['community_college_county']) {
                throw ValidationException::withMessages([
                    'community_college_site_id' => 'Please select a county and community college site.',
                ]);
            }

            $campus = Campus::query()
                ->whereKey($siteId)
                ->where('is_active', 1)
                ->where('campus_type', 'community_college')
                ->first();

            if (! $campus || $campus->county !== $validated['community_college_county']) {
                throw ValidationException::withMessages([
                    'community_college_county' => 'Please select a site from the selected county.',
                ]);
            }

            $validated['campus_id'] = (int) $siteId;
        } elseif ($selectionType === 'online') {
            $validated['campus_id'] = null;
        } else {
            throw ValidationException::withMessages([
                'campus_selection_type' => 'Please choose a campus or community college site.',
            ]);
        }

        $yearJoined = \Carbon\Carbon::parse($validated['year_joined']);

        // The student record itself stores email/phone_number so the edit form can
        // prefill values that were entered without a linked User. If a User is later
        // linked to this student, keep its email in step as well.
        $email = $validated['email'] ?? null;

        $student->update([
            'registration_number' => $validated['registration_number'],
            'program_id' => $validated['program_id'],
            'first_name' => $validated['first_name'],
            'middle_name' => $validated['middle_name'],
            'surname' => $validated['surname'],
            'cohort_intake' => $yearJoined->format('Y'),
            'current_year' => $validated['current_year'],
            'enrollment_campus_id' => $validated['campus_id'],
            'entry_pathway' => $validated['program_type'],
            'date_of_admission' => $yearJoined,
            'emergency_contact_name' => $primary['full_name'] ?? null,
            'emergency_contact_phone' => $primary['phone_number'] ?? null,
            'emergency_contact_relationship' => $primary['relationship'] ?? null,
            'email' => $email,
            'phone_number' => $validated['phone_number'] ?? null,
        ]);

        $this->syncNextOfKin($student, $nextOfKin);

        // A photo can be replaced or removed independently of the rest of the profile.
        if (! empty($validated['photo_remove']) && $student->photo_path !== null) {
            // Setting the attribute triggers PrunesStoredFiles to delete the old file.
            $student->photo_path = null;
        }

        if ($request->hasFile('photo')) {
            $student->photo_path = $this->storeStudentPhoto($student, $request->file('photo'));
        }

        if ($student->isDirty()) {
            $student->save();
        }

        if ($email && $student->user_id) {
            \App\Models\User::query()->whereKey($student->user_id)->update(['email' => $email]);
        }

        return redirect()
            ->route('administration.applications.existing-student.show', $student->id)
            ->with('success', "Student {$student->registration_number} updated successfully.");
    }

    /**
     * Persist a profile photo on the public disk and return its relative path.
     * The path is stored against the student model, and PrunesStoredFiles removes the
     * previous photo when the attribute is overwritten.
     */
    private function storeStudentPhoto(Student $student, $file): string
    {
        $directory = "students/{$student->id}";

        return $file->store($directory, 'public');
    }

    /**
     * Stored contacts for the two form slots. Older records only ever had the flat
     * emergency_contact_* columns, so the first slot falls back to those.
     *
     * @return \Illuminate\Support\Collection<int, StudentNextOfKin>
     */
    private function nextOfKinRows(Student $student): \Illuminate\Support\Collection
    {
        if (! Schema::hasTable('student_next_of_kin')) {
            return collect();
        }

        return StudentNextOfKin::query()
            ->where('student_id', $student->id)
            ->orderBy('id')
            ->get();
    }

    /**
     * The first next of kin is required, the second is optional. A partially entered
     * contact is rejected rather than saved as half a name and no number.
     *
     * @param  array<string, mixed>  $validated
     * @return list<array<string, string>>
     */
    private function nextOfKinContacts(array $validated): array
    {
        $contacts = collect($validated['next_of_kin'] ?? [])
            ->map(function ($contact) {
                return collect($contact ?? [])
                    ->map(fn ($value) => is_string($value) ? trim($value) : $value)
                    ->filter(fn ($value) => $value !== null && $value !== '')
                    ->only(['full_name', 'relationship', 'phone_number', 'alt_phone', 'email', 'physical_address'])
                    ->all();
            })
            ->reject(fn (array $contact) => $contact === [])
            ->take(2)
            ->values()
            ->all();

        $required = ['full_name' => 'name', 'relationship' => 'relationship', 'phone_number' => 'phone'];
        $errors = [];

        foreach ($contacts as $index => $contact) {
            foreach ($required as $field => $label) {
                if (isset($contact[$field])) {
                    continue;
                }

                $errors["next_of_kin.$index.$field"] = $index === 0
                    ? "The first contact needs its {$label}."
                    : 'Give the name, relationship and phone together, or leave the second contact empty.';
            }
        }

        if ($contacts === []) {
            $errors['next_of_kin.0.full_name'] = 'A first next of kin / emergency contact is required.';
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        return $contacts;
    }

    /**
     * @param  list<array<string, string>>  $contacts
     */
    private function syncNextOfKin(Student $student, array $contacts): void
    {
        if (! Schema::hasTable('student_next_of_kin')) {
            return;
        }

        StudentNextOfKin::query()->where('student_id', $student->id)->delete();

        foreach ($contacts as $contact) {
            StudentNextOfKin::create([
                'student_id' => $student->id,
                'full_name' => $contact['full_name'],
                'relationship' => $contact['relationship'],
                'phone_number' => $contact['phone_number'],
                'alt_phone' => $contact['alt_phone'] ?? null,
                'email' => $contact['email'] ?? null,
                'physical_address' => $contact['physical_address'] ?? null,
                'created_at' => now(),
                'created_by' => auth()->id(),
            ]);
        }
    }
}
