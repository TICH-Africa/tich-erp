<?php

namespace App\Services;

use App\Models\ProgramTimetable;
use App\Models\ProgramTimetableSession;
use App\Models\Staff;
use App\Models\UnitAllocation;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProgramTimetableDocumentService
{
    /**
     * Timetable is shown as the uploaded file (not a generated grid PDF).
     */
    /**
     * @return list<string>
     */
    public function allowedExtensions(): array
    {
        return config('tich-academics.timetable_upload.allowed_extensions', ['pdf', 'png', 'jpg', 'jpeg']);
    }

    public function maxUploadKilobytes(): int
    {
        return (int) config('tich-academics.timetable_upload.max_kb', 15360);
    }

    public function attachUpload(ProgramTimetable $timetable, UploadedFile $file, User $user): void
    {
        $extension = strtolower($file->getClientOriginalExtension() ?: '');
        $allowed = $this->allowedExtensions();
        if (! in_array($extension, $allowed, true)) {
            throw ValidationException::withMessages([
                'timetable_file' => 'Upload a timetable file ('.implode(', ', $allowed).').',
            ]);
        }

        $maxKb = $this->maxUploadKilobytes();
        if ($file->getSize() > $maxKb * 1024) {
            throw ValidationException::withMessages([
                'timetable_file' => 'The file is too large (max '.(int) ($maxKb / 1024).' MB).',
            ]);
        }

        if (filled($timetable->source_file)) {
            $this->deleteStoredFile((string) $timetable->source_file);
        }

        $directory = 'program-timetables/'.$timetable->program_id.'/'.$timetable->id;
        $storedName = Str::uuid()->toString().'.'.$extension;
        $path = $file->storeAs($directory, $storedName, 'local');

        $timetable->forceFill([
            'source' => 'upload',
            'source_file' => $path,
            'source_name' => $file->getClientOriginalName(),
            'source_uploaded_at' => now(),
            'source_uploaded_by' => $user->id,
            'updated_at' => now(),
        ])->save();
    }

    public function deleteStoredFile(?string $path): void
    {
        if (! filled($path)) {
            return;
        }

        if ($this->storage()->exists($path)) {
            $this->storage()->delete($path);
        }
    }

    public function detachUpload(ProgramTimetable $timetable): void
    {
        $this->deleteStoredFile($timetable->source_file);

        $timetable->forceFill([
            'source' => 'manual',
            'source_file' => null,
            'source_name' => null,
            'source_uploaded_at' => null,
            'source_uploaded_by' => null,
            'updated_at' => now(),
        ])->save();
    }

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

        $sessionCount = $this->sessionCount($timetable);

        return $sessionCount === 0;
    }

    public function hasViewableDocument(ProgramTimetable $timetable): bool
    {
        if ($this->usesUploadedFile($timetable)) {
            return true;
        }

        return $this->sessionCount($timetable) > 0;
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

    public function inlineCachedResponse(ProgramTimetable $timetable): Response
    {
        abort_unless($this->usesUploadedFile($timetable), 404);

        $binary = $this->cachedUploadedBinary($timetable);
        $filename = $this->safeFilename($this->displayFileName($timetable));

        return response($binary, 200, [
            'Content-Type' => $this->mimeType($timetable),
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
        ]);
    }

    public function cachedUploadedBinary(ProgramTimetable $timetable): string
    {
        abort_unless($this->usesUploadedFile($timetable), 404);

        $path = (string) $timetable->source_file;
        $stamp = sha1($path.'|'.(string) ($timetable->source_uploaded_at ?? '').'|'.$this->storage()->lastModified($path));

        return Cache::remember(
            'program.timetable.upload.v1.'.$timetable->id.'.'.$stamp,
            now()->addHours(6),
            fn () => $this->storage()->get($path)
        );
    }

    public function pdfCacheStamp(ProgramTimetable $timetable): string
    {
        if ($this->usesUploadedFile($timetable)) {
            $path = (string) $timetable->source_file;

            return sha1('upload|'.$path.'|'.(string) ($timetable->source_uploaded_at ?? '').'|'.$this->storage()->lastModified($path));
        }

        $sessionStamp = $timetable->relationLoaded('sessions')
            ? $timetable->sessions->max('id')
            : $timetable->sessions()->max('id');

        return sha1(
            'grid|'.(string) ($timetable->updated_at ?? '').'|'.(string) ($timetable->published_at ?? '')
            .'|'.(string) $sessionStamp
        );
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
        $isUpload = $this->usesUploadedFile($timetable);

        return [
            'source' => $timetable->source,
            'source_name' => $timetable->source_name,
            'is_upload_document' => $isUpload,
            'has_document' => $this->hasViewableDocument($timetable),
            'mime_type' => $isUpload ? $this->mimeType($timetable) : 'application/pdf',
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

    private function sessionCount(ProgramTimetable $timetable): int
    {
        if ($timetable->relationLoaded('sessions')) {
            return $timetable->sessions->count();
        }

        if (isset($timetable->sessions_count)) {
            return (int) $timetable->sessions_count;
        }

        return (int) $timetable->sessions()->count();
    }
}
