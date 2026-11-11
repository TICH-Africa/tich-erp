<?php

namespace App\Services;

use App\Models\UnitContent;
use App\Support\OfficeDocumentHtml;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

/**
 * Decides how a posted note reads on screen: inline file, rendered Word/Excel HTML, or download only.
 */
class UnitContentPreviewService
{
    public function __construct(
        private readonly OfficeDocumentHtml $office,
    ) {}

    /**
     * A Word or Excel file is stored under the type the server detected, so a .docx can land as .zip.
     * The name the lecturer uploaded keeps the real type.
     */
    public function extension(UnitContent $content): string
    {
        $known = ['docx', 'doc', 'xlsx', 'xls', 'csv', 'pptx', 'ppt', 'pdf', 'zip', 'jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'txt'];

        foreach ([$content->original_filename, $content->file_path] as $candidate) {
            $extension = strtolower((string) pathinfo((string) $candidate, PATHINFO_EXTENSION));
            if ($extension !== 'zip' && in_array($extension, $known, true)) {
                return $extension;
            }
        }

        return strtolower((string) pathinfo((string) $content->file_path, PATHINFO_EXTENSION));
    }

    /**
     * pdf, image, office, or none - what the read page shows for this note.
     */
    public function kind(UnitContent $content): string
    {
        if (blank($content->file_path)) {
            return 'none';
        }

        $extension = $this->extension($content);
        $mime = strtolower((string) $content->mime_type);

        if ($extension === 'pdf' || str_contains($mime, 'pdf')) {
            return 'pdf';
        }

        if (str_starts_with($mime, 'image/') || in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'], true)) {
            return 'image';
        }

        if ($this->office->canPreview($extension) && $this->html($content) !== null) {
            return 'office';
        }

        return 'none';
    }

    /**
     * Rendered HTML for a Word or Excel note, or null when it cannot be shown.
     */
    public function html(UnitContent $content): ?string
    {
        $extension = $this->extension($content);
        if (! $this->office->canPreview($extension)) {
            return null;
        }

        $key = $this->cacheKey($content, $extension);

        $cached = Cache::get($key);
        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        $path = $this->localPath($content);
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

    /**
     * Why a file only offers a download, in plain words, or null when it previews.
     */
    public function fallbackReason(UnitContent $content): ?string
    {
        if (blank($content->file_path)) {
            return null;
        }

        $extension = $this->extension($content);

        if ($this->office->canPreview($extension)) {
            return $this->html($content) === null
                ? 'This file could not be read for preview. Use the download button to open it.'
                : null;
        }

        return $this->office->fallbackReason($extension)
            ?? 'This file type cannot be previewed here. Use the download button to open it.';
    }

    private function cacheKey(UnitContent $content, string $extension): string
    {
        return 'learning-content.preview.v1.'.$content->id.'.'.$extension
            .'.'.md5((string) $content->file_path)
            .'.'.$content->updated_at?->getTimestamp();
    }

    private function localPath(UnitContent $content): ?string
    {
        $disk = Storage::disk('public');
        $relative = ltrim(str_replace('\\', '/', (string) $content->file_path), '/');

        if (! $disk->exists($relative)) {
            return null;
        }

        $path = $disk->path($relative);
        if (is_file($path) && is_readable($path)) {
            return $path;
        }

        $binary = $disk->get($relative);
        if (! is_string($binary) || $binary === '') {
            return null;
        }

        $temp = tempnam(sys_get_temp_dir(), 'tich-note-');
        if ($temp === false) {
            return null;
        }

        file_put_contents($temp, $binary);

        return $temp;
    }
}
