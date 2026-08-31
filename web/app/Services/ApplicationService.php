<?php

namespace App\Services;

use App\Models\Applicant;
use App\Models\ApplicationDocument;
use App\Models\CurriculumVersion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ApplicationService
{
    public function __construct(
        protected ProgramsService $programsService,
        protected AuditService $auditService,
        protected ApplicationMailService $mailService,
        protected StoredFileService $files,
    ) {}

    public function draft(): array
    {
        return session(config('tich-application.session_key'), [
            'step' => 1,
            'data' => [],
        ]);
    }

    public function currentStep(): int
    {
        return (int) ($this->draft()['step'] ?? 1);
    }

    public function saveDraft(array $data, int $step): void
    {
        $draft = $this->draft();
        $draft['data'] = array_merge($draft['data'] ?? [], $data);
        $draft['step'] = $step;
        session([config('tich-application.session_key') => $draft]);
    }

    public function setStep(int $step): void
    {
        $draft = $this->draft();
        $draft['step'] = $step;
        session([config('tich-application.session_key') => $draft]);
    }

    public function clearDraft(): void
    {
        session()->forget(config('tich-application.session_key'));
    }

    public function validateStep(Request $request, int $step): array
    {
        return match ($step) {
            1 => $this->validateProgramStep($request),
            2 => $this->validatePersonalStep($request),
            3 => $this->validateAcademicStep($request),
            4 => $this->validateSponsorshipStep($request),
            5 => $this->validateDocumentsStep($request),
            6 => $this->validateNextOfKinStep($request),
            7 => $this->validateReviewStep($request),
            default => throw ValidationException::withMessages(['step' => 'Invalid application step.']),
        };
    }

    public function submit(Request $request): Applicant
    {
        if (! Schema::hasTable('applicants')) {
            throw ValidationException::withMessages([
                'submit' => 'Applications cannot be saved yet. Please run database migrations.',
            ]);
        }

        $data = $this->draft()['data'] ?? [];
        $program = $this->programsService->findProgramById((int) ($data['program_id'] ?? 0));

        if (! $program || empty($program->id)) {
            throw ValidationException::withMessages([
                'program_id' => 'Select a valid programme before submitting.',
            ]);
        }

        $applicant = DB::transaction(function () use ($data, $program, $request) {
            $applicant = Applicant::create(
                $this->buildApplicantAttributes($data, (int) $program->id)
            );

            $this->persistUploadedDocuments($applicant, $data['documents'] ?? [], $request);

            $this->auditService->log(
                'admissions.application.submitted',
                'applicants',
                $applicant->id,
                null,
                [
                    'application_number' => $applicant->application_number,
                    'program_id' => $applicant->program_id,
                    'email' => $applicant->email,
                ],
                'Online application submitted via public portal',
                'success',
                null,
                $request
            );

            $this->clearDraft();

            return $applicant;
        });

        $applicant->load('program');
        $mailResult = $this->mailService->sendSubmissionConfirmation($applicant, $request);

        if (! $mailResult['sent']) {
            session()->flash('application_mail_error', $mailResult['error']);
        }

        $staffMail = $this->mailService->notifyStaffForReview($applicant, $request);

        if ($staffMail['sent'] === 0 && config('app.debug') && ! empty($staffMail['errors'])) {
            session()->flash('staff_mail_error', $staffMail['errors'][0]);
        }

        return $applicant;
    }

    public function lookupStatus(string $applicationNumber, string $email): ?Applicant
    {
        if (! Schema::hasTable('applicants')) {
            return null;
        }

        return Applicant::query()
            ->with('program:id,program_name,program_code')
            ->where('application_number', $applicationNumber)
            ->where('email', $email)
            ->first();
    }

    public function reviewSummary(): array
    {
        $data = $this->draft()['data'] ?? [];
        $program = $this->programsService->findProgramById((int) ($data['program_id'] ?? 0));
        $campus = collect($this->programsService->getCatalog()['campuses'])
            ->first(fn ($campus) => ($campus->id ?? null) == ($data['preferred_campus_id'] ?? null));

        return [
            'program' => $program,
            'campus' => $campus,
            'intakeLabel' => $this->intakeLabelFromData($data),
            'data' => $data,
        ];
    }

    /**
     * @return array<int, \Illuminate\Support\Collection<int, CurriculumVersion>>
     */
    public function intakesByProgram(): array
    {
        if (! Schema::hasTable('curriculum_versions')) {
            return [];
        }

        return CurriculumVersion::query()
            ->whereNotNull('intake_year')
            ->whereNotNull('intake_month')
            ->whereNotIn('status', ['superseded'])
            ->orderByDesc('intake_year')
            ->orderByDesc('intake_month')
            ->get()
            ->groupBy('program_id')
            ->all();
    }

    private function validateProgramStep(Request $request): array
    {
        $program = $this->programsService->findProgramById((int) $request->input('program_id'));

        if (! $program || empty($program->id)) {
            throw ValidationException::withMessages([
                'program_id' => 'Please select a programme from the list.',
            ]);
        }

        $programId = (int) $program->id;
        $intakes = $this->intakesForProgram($programId);
        $rules = [
            'program_id' => ['required', 'integer'],
            'preferred_campus_id' => ['nullable', 'integer'],
        ];

        if ($intakes->isNotEmpty()) {
            $rules['intake_year'] = ['required', 'integer', 'min:2000', 'max:2100'];
            $rules['intake_month'] = ['required', 'integer', 'min:1', 'max:12'];
        } else {
            $rules['intake_year'] = ['nullable', 'integer', 'min:2000', 'max:2100'];
            $rules['intake_month'] = ['nullable', 'integer', 'min:1', 'max:12'];
        }

        $validated = Validator::make($request->all(), $rules)->validate();

        if ($intakes->isNotEmpty()) {
            $validIntake = $intakes->contains(
                fn (CurriculumVersion $intake) => (int) $intake->intake_year === (int) $validated['intake_year']
                    && (int) $intake->intake_month === (int) $validated['intake_month']
            );

            if (! $validIntake) {
                throw ValidationException::withMessages([
                    'intake_year' => 'Select a valid intake for this programme.',
                ]);
            }
        }

        $validated['program_code'] = strtoupper($program->program_code ?? '');

        return $validated;
    }

    private function validatePersonalStep(Request $request): array
    {
        return Validator::make($request->all(), [
            'first_name' => ['required', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'surname' => ['required', 'string', 'max:100'],
            'date_of_birth' => ['required', 'date', 'before:today', 'after:1950-01-01'],
            'gender' => ['required', 'in:male,female,other,prefer_not_to_say'],
            'national_id_number' => ['nullable', 'string', 'max:50'],
            'passport_number' => ['nullable', 'string', 'max:50'],
            'email' => ['required', 'email', 'max:255'],
            'phone_number' => ['required', 'string', 'max:30'],
            'home_county' => ['nullable', 'string', 'max:100'],
        ])->validate();
    }

    private function validateAcademicStep(Request $request): array
    {
        return Validator::make($request->all(), [
            'entry_qualification' => ['required', 'in:'.implode(',', array_keys(config('tich-application.entry_qualifications', [])))],
            'kcse_grade' => ['nullable', 'string', 'max:20'],
            'kcse_year' => ['nullable', 'integer', 'min:1990', 'max:'.date('Y')],
            'previous_institution' => ['nullable', 'string', 'max:200'],
        ])->validate();
    }

    private function validateSponsorshipStep(Request $request): array
    {
        return Validator::make($request->all(), [
            'sponsorship_type' => ['required', 'in:'.implode(',', array_keys(config('tich-application.sponsorship_options', [])))],
            'sponsor_organization' => ['nullable', 'string', 'max:200'],
            'sponsor_address' => ['nullable', 'string', 'max:500'],
            'sponsor_phone' => ['nullable', 'string', 'max:30'],
        ])->validate();
    }

    private function validateDocumentsStep(Request $request): array
    {
        $rules = [];
        $messages = [];

        foreach (array_keys(config('tich-application.document_types', [])) as $type) {
            $uploadRules = $this->documentUploadRulesFor($type);
            $maxKb = (int) ($uploadRules['max_kb'] ?? 5120);
            $mimes = $uploadRules['mimes'] ?? 'pdf,jpg,jpeg,png';

            $fieldRules = ['nullable', 'file', 'max:'.$maxKb, 'mimes:'.$mimes];

            if ($type === 'passport_photo') {
                $fieldRules[] = 'image';
                $fieldRules[] = 'mimetypes:image/jpeg,image/png,image/webp';
                $fieldRules[] = 'dimensions:max_width=4000,max_height=4000';
            }

            $rules["documents.{$type}"] = $fieldRules;
            $messages["documents.{$type}.image"] = 'The passport photo must be an image file (JPEG, PNG, or WebP). PDFs are not accepted.';
            $messages["documents.{$type}.mimes"] = $type === 'passport_photo'
                ? 'The passport photo must be a JPEG, PNG, or WebP image.'
                : 'This document must be a PDF or image (JPEG/PNG).';
            $messages["documents.{$type}.max"] = $type === 'passport_photo'
                ? 'The passport photo must not be larger than 2 MB.'
                : 'Each document must not be larger than 5 MB.';
            $messages["documents.{$type}.mimetypes"] = 'The passport photo must be a JPEG, PNG, or WebP image.';
        }

        Validator::make($request->all(), $rules, $messages)->validate();

        $existing = $this->draft()['data']['documents'] ?? [];

        foreach (array_keys(config('tich-application.document_types', [])) as $type) {
            if ($request->hasFile("documents.{$type}")) {
                $file = $request->file("documents.{$type}");
                $storedPath = $this->files->store($file, 'applications/pending', 'local');
                $existing[$type] = [
                    'temp_path' => $storedPath,
                    'original_filename' => $file->getClientOriginalName(),
                    'mime_type' => $this->storedMimeType($storedPath, $file->getMimeType()),
                ];
            }
        }

        return ['documents' => $existing];
    }

    private function validateReviewStep(Request $request): array
    {
        Validator::make($request->all(), [
            'confirm_accuracy' => ['accepted'],
            'confirm_terms' => ['accepted'],
        ])->validate();

        return [];
    }

    private function validateNextOfKinStep(Request $request): array
    {
        return Validator::make($request->all(), [
            'next_of_kin_name' => ['required', 'string', 'max:200'],
            'next_of_kin_relationship' => ['required', 'in:'.implode(',', array_keys(config('tich-application.next_of_kin_relationships', [])))],
            'next_of_kin_address' => ['nullable', 'string', 'max:500'],
            'next_of_kin_phone' => ['required', 'string', 'max:30'],
        ])->validate();
    }

    private function persistUploadedDocuments(Applicant $applicant, array $documents, Request $request): void
    {
        if (! Schema::hasTable('application_documents')) {
            return;
        }

        foreach ($documents as $type => $meta) {
            if (empty($meta['temp_path'])) {
                continue;
            }

            $filename = basename($meta['temp_path']);
            $destination = "applications/{$applicant->id}/{$filename}";
            $destination = $this->files->move($meta['temp_path'], $destination, 'local');

            ApplicationDocument::create([
                'applicant_id' => $applicant->id,
                'document_type' => $type,
                'file_path' => $destination,
                'original_filename' => $meta['original_filename'] ?? $filename,
                'mime_type' => $this->storedMimeType($destination, $meta['mime_type'] ?? 'application/octet-stream'),
            ]);
        }
    }

    private function buildApplicantAttributes(array $data, int $programId): array
    {
        $firstName = trim($data['first_name']);
        if (! Schema::hasColumn('applicants', 'middle_name') && ! empty($data['middle_name'])) {
            $firstName = trim($firstName.' '.$data['middle_name']);
        }

        $attributes = [
            'application_number' => $this->generateApplicationNumber(),
            'program_id' => $programId,
            'intake_year' => $data['intake_year'] ?? null,
            'intake_month' => $data['intake_month'] ?? null,
            'handling_department_id' => $this->resolveProgramDepartmentId($programId),
            'preferred_campus_id' => $data['preferred_campus_id'] ?? null,
            'first_name' => $firstName,
            'middle_name' => $data['middle_name'] ?? null,
            'surname' => $data['surname'],
            'date_of_birth' => $data['date_of_birth'],
            'gender' => $data['gender'],
            'national_id_number' => $data['national_id_number'] ?? null,
            'passport_number' => $data['passport_number'] ?? null,
            'email' => $data['email'],
            'phone_number' => $data['phone_number'],
            'home_county' => $data['home_county'] ?? null,
            'entry_qualification' => $data['entry_qualification'],
            'kcse_grade' => $data['kcse_grade'] ?? null,
            'kcse_year' => $data['kcse_year'] ?? null,
            'previous_institution' => $data['previous_institution'] ?? null,
            'sponsorship_type' => $data['sponsorship_type'] ?? null,
            'sponsor_organization' => $data['sponsor_organization'] ?? null,
            'sponsor_address' => $data['sponsor_address'] ?? null,
            'sponsor_phone' => $data['sponsor_phone'] ?? null,
            'next_of_kin_name' => $data['next_of_kin_name'] ?? null,
            'next_of_kin_relationship' => $data['next_of_kin_relationship'] ?? null,
            'next_of_kin_phone' => $data['next_of_kin_phone'] ?? null,
            'next_of_kin_address' => $data['next_of_kin_address'] ?? null,
            'status' => 'submitted_admin',
            'academic_review_status' => 'pending',
            'application_source' => 'online',
        ];

        return collect($attributes)
            ->filter(fn ($value, $key) => Schema::hasColumn('applicants', $key))
            ->all();
    }

    private function generateApplicationNumber(): string
    {
        $year = date('Y');
        $latest = Applicant::query()
            ->where('application_number', 'like', "APP-{$year}-%")
            ->orderByDesc('id')
            ->value('application_number');

        $sequence = 1;
        if ($latest && preg_match('/APP-\d{4}-(\d+)/', $latest, $matches)) {
            $sequence = (int) $matches[1] + 1;
        }

        return sprintf('APP-%s-%05d', $year, $sequence);
    }

    private function resolveProgramDepartmentId(int $programId): ?int
    {
        if (! Schema::hasColumn('applicants', 'handling_department_id')) {
            return null;
        }

        return DB::table('academic_programs')->where('id', $programId)->value('department_id');
    }

    /**
     * @return \Illuminate\Support\Collection<int, CurriculumVersion>
     */
    private function intakesForProgram(int $programId): \Illuminate\Support\Collection
    {
        if (! Schema::hasTable('curriculum_versions')) {
            return collect();
        }

        return CurriculumVersion::query()
            ->where('program_id', $programId)
            ->whereNotNull('intake_year')
            ->whereNotNull('intake_month')
            ->whereNotIn('status', ['superseded'])
            ->orderByDesc('intake_year')
            ->orderByDesc('intake_month')
            ->get();
    }

    private function intakeLabelFromData(array $data): ?string
    {
        $year = $data['intake_year'] ?? null;
        $month = $data['intake_month'] ?? null;

        if (! $year || ! $month) {
            return null;
        }

        $monthName = CurriculumVersion::intakeMonths()[(int) $month] ?? (string) $month;

        return "{$monthName} {$year} intake";
    }

    /**
     * @return array{accept: string, mimes: string, max_kb: int, hint: string}
     */
    public function documentUploadRulesFor(string $type): array
    {
        $defaults = config('tich-application.document_upload_rules.default', []);
        $specific = config("tich-application.document_upload_rules.{$type}", []);

        return array_merge($defaults, $specific);
    }

    private function storedMimeType(string $path, ?string $fallback = null): string
    {
        if (str_ends_with(strtolower($path), '.webp')) {
            return 'image/webp';
        }

        return $fallback ?: 'application/octet-stream';
    }
}
