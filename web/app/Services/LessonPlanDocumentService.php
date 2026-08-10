<?php

namespace App\Services;

use App\Models\LessonPlan;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class LessonPlanDocumentService
{
    public function __construct(
        protected PrintDocumentService $printDocuments,
        protected RBACService $rbac,
        protected StoredFileService $files,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function documentPayload(LessonPlan $plan): array
    {
        $plan->loadMissing(['allocation.unit', 'allocation.semester.academicYear', 'allocation.campus', 'preparedByStaff']);
        $allocation = $plan->allocation;
        $unit = $allocation?->unit;
        $tutor = $plan->preparedByStaff;
        $payload = is_array($plan->form_payload) ? $plan->form_payload : [];

        return [
            'plan' => $plan,
            'allocation' => $allocation,
            'unit' => $unit,
            'tutor' => $tutor,
            'payload' => $payload,
            'formFieldLabels' => config('tich-lesson-plans.form_fields', []),
            'documentTitle' => 'Lesson plan',
            'documentSubtitle' => trim(($unit?->unit_code ?? '').' · '.($plan->lesson_title ?: ($plan->topics_covered ?: 'Session plan'))),
            'documentRef' => $plan->plan_number,
            'showSignatures' => true,
            'paperOrientation' => 'portrait',
            'metaRows' => [
                ['label' => 'Plan no.', 'value' => e($plan->plan_number)],
                ['label' => 'Unit', 'value' => e(trim(($unit?->unit_code ?? '-').' · '.($unit?->unit_name ?? '')))],
                ['label' => 'Lesson topic', 'value' => e($plan->lesson_title ?: ($plan->topics_covered ?: '-'))],
                ['label' => 'Facilitator', 'value' => e($tutor?->fullName() ?? '-')],
                ['label' => 'Planned date', 'value' => e($plan->planned_date?->format('d F Y') ?? '-')],
                ['label' => 'Week', 'value' => e((string) ($plan->week_number ?? '-'))],
                ['label' => 'Duration', 'value' => e($plan->contact_hours.' contact hour(s)')],
                ['label' => 'Venue', 'value' => e($payload['venue'] ?? '-')],
                ['label' => 'Session time', 'value' => e($payload['session_time'] ?? '-')],
                ['label' => 'Class / intake', 'value' => e($payload['intake_class'] ?? ($allocation?->intake_label ?? '-'))],
            ],
        ];
    }

    public function storeUpload(Staff $staff, UploadedFile $file): array
    {
        $extension = strtolower($file->getClientOriginalExtension());
        abort_unless(in_array($extension, config('tich-lesson-plans.upload.allowed_extensions', []), true), 422, 'Upload a PDF or Word document (.pdf, .doc, .docx).');

        $directory = 'lesson-plans/'.$staff->id.'/'.now()->format('Y/m');
        $storedName = Str::uuid()->toString().'.'.$extension;
        $path = $file->storeAs($directory, $storedName, 'local');

        return [
            'uploaded_file_path' => $path,
            'uploaded_file_name' => $file->getClientOriginalName(),
        ];
    }

    public function deleteUpload(?string $path): void
    {
        $this->files->delete($path, 'local');
    }

    public function canView(LessonPlan $plan, User $user, ?Staff $staff): bool
    {
        if ($staff && (int) $plan->prepared_by === (int) $staff->id) {
            return true;
        }

        if ($this->rbac->hasAnyRole($user, ['Super Admin', 'CEO', 'Dean', 'Academic Registrar', 'QA Officer', 'HOD'])) {
            return in_array($plan->status, ['submitted', 'approved', 'modified', 'rejected'], true)
                || (bool) $plan->registrar_visible;
        }

        return false;
    }

    public function assertCanView(LessonPlan $plan, User $user, ?Staff $staff): void
    {
        abort_unless($this->canView($plan, $user, $staff), 403);
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function normalizeFormPayload(array $input): array
    {
        $payload = [];

        foreach (array_keys(config('tich-lesson-plans.form_fields', [])) as $key) {
            $value = trim((string) ($input[$key] ?? ''));
            if ($value !== '') {
                $payload[$key] = $value;
            }
        }

        $rows = $this->normalizeSessionRows($input['session_rows'] ?? []);
        if ($rows !== []) {
            $payload['session_rows'] = $rows;
        }

        return $payload;
    }

    /**
     * @param  mixed  $rows
     * @return list<array<string, string>>
     */
    public function normalizeSessionRows(mixed $rows): array
    {
        if (! is_array($rows)) {
            return [];
        }

        $columns = array_keys(config('tich-lesson-plans.session_row_columns', []));
        $normalized = [];

        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            $entry = [];
            $hasContent = false;

            foreach ($columns as $column) {
                $value = trim((string) ($row[$column] ?? ''));
                $entry[$column] = $value;
                if ($value !== '') {
                    $hasContent = true;
                }
            }

            if ($hasContent) {
                $normalized[] = $entry;
            }
        }

        return $normalized;
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    public function mapValidatedPlanData(array $validated, string $sourceType): array
    {
        $data = [
            'lesson_title' => trim((string) ($validated['lesson_title'] ?? '')) ?: null,
            'topics_covered' => trim((string) ($validated['topics_covered'] ?? '')) ?: null,
            'competencies_targeted' => trim((string) ($validated['competencies_targeted'] ?? '')) ?: null,
            'contact_hours' => (int) ($validated['contact_hours'] ?? 2),
            'week_number' => (int) ($validated['week_number'] ?? 1),
            'planned_date' => $validated['planned_date'],
            'teaching_methods' => trim((string) ($validated['teaching_methods'] ?? '')) ?: null,
            'resources_required' => trim((string) ($validated['resources_required'] ?? '')) ?: null,
            'source_type' => $sourceType,
        ];

        if ($sourceType === 'form') {
            $data['lesson_objectives'] = trim((string) ($validated['lesson_objectives'] ?? ''));
            $data['form_payload'] = $this->normalizeFormPayload($validated);
            $data['topics_covered'] = $data['topics_covered'] ?: $data['lesson_title'];
            $data['teaching_methods'] = $data['teaching_methods'] ?: $this->summarizeTeachingMethods($data['form_payload']);
        } else {
            $data['lesson_objectives'] = trim((string) ($validated['lesson_objectives'] ?? ''))
                ?: 'Refer to the attached lesson plan document.';
            $data['form_payload'] = null;
            $data['tutor_verified_at'] = null;
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function summarizeTeachingMethods(array $payload): ?string
    {
        $rows = $payload['session_rows'] ?? [];
        if (! is_array($rows) || $rows === []) {
            return null;
        }

        $snippets = collect($rows)
            ->pluck('trainer_activities')
            ->filter(fn ($value) => trim((string) $value) !== '')
            ->take(3)
            ->map(fn ($value) => \Illuminate\Support\Str::limit(trim((string) $value), 80))
            ->all();

        return $snippets === [] ? null : implode('; ', $snippets);
    }
}
