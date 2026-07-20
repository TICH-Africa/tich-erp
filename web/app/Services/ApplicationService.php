<?php

namespace App\Services;

use App\Models\Applicant;
use App\Models\ApplicationDocument;
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
            4 => $this->validateDocumentsStep($request),
            5 => $this->validateReviewStep($request),
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

        return DB::transaction(function () use ($data, $program, $request) {
            $applicant = Applicant::create([
                'application_number' => $this->generateApplicationNumber(),
                'program_id' => $program->id,
                'preferred_campus_id' => $data['preferred_campus_id'] ?? null,
                'first_name' => $data['first_name'],
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
                'status' => 'submitted',
                'academic_review_status' => 'pending',
                'application_source' => 'online',
            ]);

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
    }

    public function lookupStatus(string $applicationNumber, string $email): ?Applicant
    {
        if (! Schema::hasTable('applicants')) {
            return null;
        }

        return Applicant::query()
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
            'data' => $data,
        ];
    }

    private function validateProgramStep(Request $request): array
    {
        $validated = Validator::make($request->all(), [
            'program_id' => ['required', 'integer'],
            'preferred_campus_id' => ['nullable', 'integer'],
        ])->validate();

        $program = $this->programsService->findProgramById((int) $validated['program_id']);

        if (! $program || empty($program->id)) {
            throw ValidationException::withMessages([
                'program_id' => 'Please select a programme from the list.',
            ]);
        }

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

    private function validateDocumentsStep(Request $request): array
    {
        $rules = [];
        foreach (array_keys(config('tich-application.document_types', [])) as $type) {
            $rules["documents.{$type}"] = ['nullable', 'file', 'max:5120', 'mimes:pdf,jpg,jpeg,png'];
        }

        Validator::make($request->all(), $rules)->validate();

        $existing = $this->draft()['data']['documents'] ?? [];

        foreach (array_keys(config('tich-application.document_types', [])) as $type) {
            if ($request->hasFile("documents.{$type}")) {
                $file = $request->file("documents.{$type}");
                $existing[$type] = [
                    'temp_path' => $file->store('applications/pending', 'local'),
                    'original_filename' => $file->getClientOriginalName(),
                    'mime_type' => $file->getMimeType(),
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

            if (Storage::disk('local')->exists($meta['temp_path'])) {
                Storage::disk('local')->move($meta['temp_path'], $destination);
            }

            ApplicationDocument::create([
                'applicant_id' => $applicant->id,
                'document_type' => $type,
                'file_path' => $destination,
                'original_filename' => $meta['original_filename'] ?? $filename,
                'mime_type' => $meta['mime_type'] ?? 'application/octet-stream',
            ]);
        }
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
}
