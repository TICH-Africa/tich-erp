<?php

namespace App\Services;

use App\Models\ProgramTimetable;
use App\Support\OfficeDocumentHtml;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

/**
 * Turns an uploaded .docx / .xlsx / .xls / .csv timetable into browser HTML.
 */
class TimetableDocumentPreviewService
{
    public function __construct(
        private readonly ProgramTimetableDocumentService $documents,
        private readonly OfficeDocumentHtml $office,
        private readonly PrintDocumentService $print,
    ) {}

    /**
     * PDF bytes of the uploaded document, cached like the HTML, or '' when it cannot be rendered.
     */
    public function cachedPdfBinary(ProgramTimetable $timetable): string
    {
        $key = 'program.timetable.document.pdf.v1.'.$timetable->id.'.'.$this->documents->pdfCacheStamp($timetable);

        $cached = Cache::get($key);
        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        $html = $this->html($timetable);
        if (! is_string($html) || trim($html) === '') {
            return '';
        }

        $binary = $this->print->pdfBinary('academics.programs.timetable-document-print', [
            'timetable' => $timetable,
            'documentHtml' => $html,
            'documentTitle' => $timetable->displayTitle(),
            'documentSubtitle' => $this->subtitle($timetable),
            'paperOrientation' => 'landscape',
        ], 'landscape');

        Cache::put($key, $binary, now()->addHours(6));

        return $binary;
    }

    private function subtitle(ProgramTimetable $timetable): string
    {
        $parts = array_filter([
            $timetable->program?->program_name,
            $timetable->campus?->campus_name,
            $timetable->cycleLabel(),
        ]);

        return implode(' · ', $parts);
    }

    /**
     * Rendered HTML for the uploaded document, or null when it cannot be shown.
     */
    public function html(ProgramTimetable $timetable): ?string
    {
        if (! $this->documents->usesUploadedFile($timetable)) {
            return null;
        }

        $extension = strtolower(pathinfo((string) $timetable->source_file, PATHINFO_EXTENSION));

        if (! in_array($extension, ProgramTimetableDocumentService::PREVIEW_EXTENSIONS, true)) {
            return null;
        }

        $key = 'program.timetable.preview.v1.'.$timetable->id.'.'.$this->documents->pdfCacheStamp($timetable);

        $cached = Cache::get($key);
        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        $path = $this->localPath($timetable);
        if ($path === null) {
            return null;
        }

        $html = $this->office->html($path, $extension);
        if ($html === null) {
            return null;
        }

        Cache::put($key, $html, now()->addHours(6));

        return $html;
    }

    public function fallbackReason(ProgramTimetable $timetable): ?string
    {
        $extension = strtolower(pathinfo((string) $timetable->source_file, PATHINFO_EXTENSION));

        return $this->office->fallbackReason($extension);
    }

    private function localPath(ProgramTimetable $timetable): ?string
    {
        $disk = Storage::disk('local');
        $relative = (string) $timetable->source_file;

        if ($disk->exists($relative)) {
            $path = $disk->path($relative);
            if (is_file($path) && is_readable($path)) {
                return $path;
            }
        }

        $binary = $this->documents->cachedUploadedBinary($timetable);
        if ($binary === '') {
            return null;
        }

        $temp = tempnam(sys_get_temp_dir(), 'tich-timetable-');
        if ($temp === false) {
            return null;
        }

        file_put_contents($temp, $binary);

        return $temp;
    }
}
