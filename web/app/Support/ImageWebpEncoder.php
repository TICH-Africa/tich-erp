<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Throwable;

class ImageWebpEncoder
{
    public function isAvailable(): bool
    {
        return function_exists('imagewebp') && function_exists('imagecreatefromstring');
    }

    public function mimeOf(UploadedFile $file): string
    {
        return strtolower((string) ($file->getMimeType() ?: ''));
    }

    /**
     * Raster images that must be stored as .webp (upload only — no remote URLs).
     */
    public function isRasterImageUpload(UploadedFile $file): bool
    {
        $mime = $this->mimeOf($file);

        if ($mime === 'image/svg+xml') {
            return false;
        }

        if (str_starts_with($mime, 'image/')) {
            return true;
        }

        $path = $file->getRealPath();

        return $path !== false && $this->isImageBinary((string) file_get_contents($path));
    }

    public function isAlreadyWebp(UploadedFile $file): bool
    {
        $mime = $this->mimeOf($file);

        if ($mime === 'image/webp') {
            return true;
        }

        $extension = strtolower((string) $file->getClientOriginalExtension());

        return $extension === 'webp';
    }

    public function shouldConvertUploadedFile(UploadedFile $file): bool
    {
        if (! $this->isAvailable()) {
            return false;
        }

        if (! $this->isRasterImageUpload($file)) {
            return false;
        }

        $mime = $this->mimeOf($file);

        if ($this->shouldSkipMime($mime)) {
            return false;
        }

        return true;
    }

    public function shouldConvertBinary(string $binary): bool
    {
        if (! $this->isAvailable() || $binary === '') {
            return false;
        }

        $mime = $this->detectMimeFromBinary($binary);

        if ($mime && $this->shouldSkipMime($mime)) {
            return false;
        }

        return $this->isImageBinary($binary);
    }

    public function encodeUploadedFile(UploadedFile $file, ?int $quality = null): ?string
    {
        return $this->encodeFromBinary((string) file_get_contents($file->getRealPath()), $quality);
    }

    public function encodeFromBinary(string $binary, ?int $quality = null): ?string
    {
        if (! $this->shouldConvertBinary($binary)) {
            return null;
        }

        $quality = $quality ?? config('tich-media.webp_quality', 85);

        try {
            $image = @imagecreatefromstring($binary);
            if ($image === false) {
                return null;
            }

            $this->preserveAlpha($image);

            ob_start();
            imagewebp($image, null, max(0, min(100, $quality)));
            $webp = ob_get_clean() ?: null;
            imagedestroy($image);

            return $webp !== '' ? $webp : null;
        } catch (Throwable $e) {
            Log::warning('WebP conversion failed', ['message' => $e->getMessage()]);

            return null;
        }
    }

    public function toWebpFilename(?string $filename, ?string $fallback = null): string
    {
        if ($filename) {
            $stem = pathinfo($filename, PATHINFO_FILENAME);

            return ($stem !== '' ? $stem : ($fallback ?? uniqid('img_', true))).'.webp';
        }

        return ($fallback ?? uniqid('img_', true)).'.webp';
    }

    private function shouldSkipMime(string $mime): bool
    {
        return in_array($mime, config('tich-media.webp_skip_mimes', []), true);
    }

    private function isImageBinary(string $binary): bool
    {
        if ($binary === '') {
            return false;
        }

        return @getimagesizefromstring($binary) !== false;
    }

    private function detectMimeFromBinary(string $binary): ?string
    {
        $info = @getimagesizefromstring($binary);

        return is_array($info) && isset($info['mime']) ? strtolower((string) $info['mime']) : null;
    }

    /**
     * @param  \GdImage  $image
     */
    private function preserveAlpha($image): void
    {
        if (! function_exists('imagealphablending') || ! function_exists('imagesavealpha')) {
            return;
        }

        imagealphablending($image, true);
        imagesavealpha($image, true);
    }
}
