<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SupportingDocumentResponse
{
    public static function isPreviewable(?string $mime, ?string $filename = null): bool
    {
        $mime = strtolower(trim((string) $mime));
        if (str_starts_with($mime, 'image/') || $mime === 'application/pdf') {
            return true;
        }

        $ext = strtolower(pathinfo((string) $filename, PATHINFO_EXTENSION));

        return in_array($ext, ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'webp'], true);
    }

    public static function resolve(array $docs, int $index): array
    {
        abort_unless(isset($docs[$index]) && is_array($docs[$index]), 404);
        $file = $docs[$index];
        $path = (string) ($file['path'] ?? '');
        abort_unless($path !== '' && Storage::disk('local')->exists($path), 404);

        return [
            'path' => $path,
            'original_name' => (string) ($file['original_name'] ?? basename($path)),
            'mime' => (string) ($file['mime'] ?? 'application/octet-stream'),
            'size' => $file['size'] ?? null,
        ];
    }

    public static function view(array $docs, int $index): StreamedResponse
    {
        $file = self::resolve($docs, $index);
        $safeName = str_replace(['"', "\r", "\n"], '', $file['original_name']);

        return Storage::disk('local')->response(
            $file['path'],
            $file['original_name'],
            [
                'Content-Type' => $file['mime'],
                'Content-Disposition' => 'inline; filename="'.$safeName.'"',
            ]
        );
    }

    public static function download(array $docs, int $index): StreamedResponse
    {
        $file = self::resolve($docs, $index);

        return Storage::disk('local')->download(
            $file['path'],
            $file['original_name'],
            ['Content-Type' => $file['mime']]
        );
    }
}
