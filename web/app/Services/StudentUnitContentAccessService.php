<?php

namespace App\Services;

use App\Models\Student;
use App\Models\UnitContent;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StudentUnitContentAccessService
{
    public function __construct(
        protected StudentAcademicRecordService $academicRecords,
        protected StudentPortalDashboardService $dashboard,
    ) {}

    public function resolveForStudent(Student $student, UnitContent $content): UnitContent
    {
        abort_unless($content->isAvailable(), 404);

        $allowedUnitIds = $this->allowedUnitIds($student);
        abort_unless(in_array((int) $content->unit_id, $allowedUnitIds, true), 403);

        return $content;
    }

    /**
     * @return list<int>
     */
    public function allowedUnitIds(Student $student): array
    {
        $mayViewProvisional = $this->dashboard->mayViewProvisionalCurriculum($student);
        $data = $this->academicRecords->mobileUnitsForStudent($student, $mayViewProvisional);

        return collect($data['registered_units'] ?? [])
            ->pluck('unit_id')
            ->merge(collect($data['curriculum_units'] ?? [])->pluck('unit_id'))
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    public function inlineFileResponse(UnitContent $content): StreamedResponse
    {
        $path = $this->resolvePublicPath($content);

        return Storage::disk('public')->response(
            $path,
            $this->safeFilename($content),
            [
                'Content-Type' => $content->mime_type ?: 'application/octet-stream',
                'Content-Disposition' => 'inline; filename="'.$this->safeFilename($content).'"',
            ]
        );
    }

    public function downloadFileResponse(UnitContent $content): StreamedResponse
    {
        $path = $this->resolvePublicPath($content);

        return Storage::disk('public')->download(
            $path,
            $this->safeFilename($content),
            ['Content-Type' => $content->mime_type ?: 'application/octet-stream']
        );
    }

    private function resolvePublicPath(UnitContent $content): string
    {
        abort_unless(filled($content->file_path), 404);

        $path = ltrim(str_replace('\\', '/', (string) $content->file_path), '/');
        if (str_starts_with($path, 'storage/')) {
            $path = substr($path, strlen('storage/'));
        }

        abort_unless(Storage::disk('public')->exists($path), 404);

        return $path;
    }

    private function safeFilename(UnitContent $content): string
    {
        $name = trim((string) ($content->original_filename ?: $content->title ?: 'learning-content'));
        $name = preg_replace('/[^\w.\-() ]+/u', '_', $name) ?: 'learning-content';

        if (! str_contains($name, '.') && filled($content->mime_type)) {
            $ext = match ($content->mime_type) {
                'application/pdf' => 'pdf',
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                default => null,
            };
            if ($ext) {
                $name .= '.'.$ext;
            }
        }

        return $name;
    }
}
