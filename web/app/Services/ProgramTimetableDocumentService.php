<?php

namespace App\Services;

use App\Models\ProgramTimetable;
use App\Models\ProgramTimetableSession;
use App\Models\Staff;
use App\Models\UnitAllocation;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProgramTimetableDocumentService
{
    /**
     * Timetable is shown as the uploaded file (not a generated grid PDF).
     */
    public function usesUploadedFile(ProgramTimetable $timetable): bool
    {
        if (! filled($timetable->source_file)) {
            return false;
        }

        if (! $this->storage()->exists((string) $timetable->source_file)) {
            return false;
        }

        if (in_array((string) $timetable->source, ['upload', 'file'], true)) {
            return true;
        }

        $sessionCount = $timetable->relationLoaded('sessions')
            ? $timetable->sessions->count()
            : $timetable->sessions()->count();

        return $sessionCount === 0;
    }

    public function hasViewableDocument(ProgramTimetable $timetable): bool
    {
        if ($this->usesUploadedFile($timetable)) {
            return true;
        }

        $sessionCount = $timetable->relationLoaded('sessions')
            ? $timetable->sessions->count()
            : $timetable->sessions()->count();

        return $sessionCount > 0;
    }

    public function displayFileName(ProgramTimetable $timetable): string
    {
        $name = trim((string) ($timetable->source_name ?? ''));

        return $name !== '' ? $name : basename((string) $timetable->source_file);
    }

    public function mimeType(ProgramTimetable $timetable): string
    {
        $path = (string) $timetable->source_file;

        return $this->storage()->mimeType($path) ?: 'application/octet-stream';
    }

    public function inlineResponse(ProgramTimetable $timetable): StreamedResponse
    {
        abort_unless($this->usesUploadedFile($timetable), 404);

        $path = (string) $timetable->source_file;
        $filename = $this->safeFilename($this->displayFileName($timetable));

        return $this->storage()->response($path, $filename, [
            'Content-Type' => $this->mimeType($timetable),
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
        ]);
    }

    public function downloadResponse(ProgramTimetable $timetable): StreamedResponse
    {
        abort_unless($this->usesUploadedFile($timetable), 404);

        $path = (string) $timetable->source_file;
        $filename = $this->safeFilename($this->displayFileName($timetable));

        return $this->storage()->download($path, $filename, [
            'Content-Type' => $this->mimeType($timetable),
        ]);
    }

    public function staffCanViewTimetable(Staff $staff, ProgramTimetable $timetable): bool
    {
        $onSessions = ProgramTimetableSession::query()
            ->where('program_timetable_id', $timetable->id)
            ->where('staff_id', $staff->id)
            ->exists();

        if ($onSessions) {
            return true;
        }

        if (! $this->usesUploadedFile($timetable)) {
            return false;
        }

        return $this->staffHasProgramAllocation($staff, (int) $timetable->program_id, (int) $timetable->teaching_period);
    }

    /**
     * @return array<string, mixed>
     */
    public function mobileDocumentMeta(ProgramTimetable $timetable): array
    {
        return [
            'source' => $timetable->source,
            'source_name' => $timetable->source_name,
            'is_upload_document' => $this->usesUploadedFile($timetable),
            'has_document' => $this->hasViewableDocument($timetable),
        ];
    }

    /**
     * Published / draft upload timetables for programmes the lecturer is allocated to.
     *
     * @return list<int>
     */
    public function uploadTimetableIdsForStaff(Staff $staff): array
    {
        $allocations = UnitAllocation::query()
            ->with(['unit:id,program_id', 'semester:id,semester_number'])
            ->where('staff_id', $staff->id)
            ->get();

        if ($allocations->isEmpty()) {
            return [];
        }

        $ids = [];

        $scopes = [];
        foreach ($allocations as $allocation) {
            $programId = (int) ($allocation->unit?->program_id ?? 0);
            $period = (int) ($allocation->semester?->semester_number ?? 0);
            if ($programId <= 0 || $period <= 0) {
                continue;
            }
            $scopes[$programId.'-'.$period] = ['program_id' => $programId, 'period' => $period];
        }

        if ($scopes === []) {
            return [];
        }

        ProgramTimetable::query()
            ->whereNotNull('source_file')
            ->where('source_file', '!=', '')
            ->whereIn('status', ['published', 'draft'])
            ->where(function ($query) use ($scopes) {
                foreach ($scopes as $scope) {
                    $query->orWhere(function ($inner) use ($scope) {
                        $inner->where('program_id', $scope['program_id'])
                            ->where('teaching_period', $scope['period']);
                    });
                }
            })
            ->orderBy('teaching_period')
            ->orderBy('timetable_kind')
            ->each(function (ProgramTimetable $timetable) use (&$ids) {
                if ($this->usesUploadedFile($timetable)) {
                    $ids[] = (int) $timetable->id;
                }
            });

        return array_values(array_unique($ids));
    }

    private function staffHasProgramAllocation(Staff $staff, int $programId, int $teachingPeriod): bool
    {
        return UnitAllocation::query()
            ->where('staff_id', $staff->id)
            ->whereHas('unit', fn ($query) => $query->where('program_id', $programId))
            ->whereHas('semester', fn ($query) => $query->where('semester_number', $teachingPeriod))
            ->exists();
    }

    private function storage(): \Illuminate\Contracts\Filesystem\Filesystem
    {
        return Storage::disk('local');
    }

    private function safeFilename(string $name): string
    {
        return str_replace(['"', "\r", "\n"], '', $name);
    }
}
