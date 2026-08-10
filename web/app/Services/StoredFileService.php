<?php

namespace App\Services;

use App\Support\ImageWebpEncoder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Central file storage helper. Images are converted to WebP before saving.
 * Prefer model updates with {@see \App\Models\Concerns\PrunesStoredFiles}
 * or {@see replace()} so previous files are removed when paths change.
 */
class StoredFileService
{
    public function __construct(
        protected ImageWebpEncoder $webp,
    ) {}

    public function relativePath(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return null;
        }

        if (str_starts_with($path, 'storage/')) {
            return substr($path, 8);
        }

        return ltrim($path, '/');
    }

    public function pathsMatch(?string $first, ?string $second, string $disk = 'public'): bool
    {
        $a = $this->relativePath($first);
        $b = $this->relativePath($second);

        if (! $a || ! $b) {
            return false;
        }

        if ($a === $b) {
            return true;
        }

        if (Storage::disk($disk)->exists($a) && Storage::disk($disk)->exists($b)) {
            return Storage::disk($disk)->path($a) === Storage::disk($disk)->path($b);
        }

        return false;
    }

    public function delete(?string $path, string $disk = 'public'): void
    {
        $relative = $this->relativePath($path);

        if ($relative && Storage::disk($disk)->exists($relative)) {
            Storage::disk($disk)->delete($relative);
        }
    }

    public function store(UploadedFile $file, string $directory, string $disk = 'public', ?string $filename = null): string
    {
        $directory = trim($directory, '/');

        if ($webpPath = $this->storeAsWebp($file, $directory, $disk, $filename)) {
            return $webpPath;
        }

        if ($filename) {
            return $file->storeAs($directory, $filename, $disk);
        }

        return $file->store($directory, $disk);
    }

    public function replace(
        ?string $oldPath,
        UploadedFile $file,
        string $directory,
        string $disk = 'public',
        ?string $filename = null,
        bool $publicStoragePrefix = false,
    ): string {
        $path = $this->store($file, $directory, $disk, $filename);

        if ($oldPath && ! $this->pathsMatch($oldPath, $path, $disk)) {
            $this->delete($oldPath, $disk);
        }

        return $publicStoragePrefix && $disk === 'public' ? 'storage/'.$path : $path;
    }

    public function put(string $contents, string $path, string $disk = 'public', ?string $replacePath = null): string
    {
        $relative = $this->relativePath($path) ?? ltrim($path, '/');

        if ($webp = $this->webp->encodeFromBinary($contents)) {
            $contents = $webp;
            $relative = $this->webpPathForTarget($relative);
        }

        $directory = dirname($relative);

        if ($directory !== '.' && $directory !== '') {
            Storage::disk($disk)->makeDirectory($directory);
        }

        Storage::disk($disk)->put($relative, $contents);

        if ($replacePath && ! $this->pathsMatch($replacePath, $relative, $disk)) {
            $this->delete($replacePath, $disk);
        }

        return $relative;
    }

    public function move(string $fromPath, string $toPath, string $disk = 'public', ?string $replacePath = null): string
    {
        $from = $this->relativePath($fromPath);
        $to = $this->relativePath($toPath) ?? ltrim($toPath, '/');

        if ($from && Storage::disk($disk)->exists($from)) {
            Storage::disk($disk)->makeDirectory(dirname($to));

            if ($this->shouldConvertStoredFile($from, $disk)) {
                $binary = Storage::disk($disk)->get($from);
                $webp = $this->webp->encodeFromBinary($binary);
                $to = $this->webpPathForTarget($to);
                Storage::disk($disk)->put($to, $webp ?? $binary);
                Storage::disk($disk)->delete($from);
            } else {
                Storage::disk($disk)->move($from, $to);
            }
        }

        if ($replacePath && ! $this->pathsMatch($replacePath, $to, $disk)) {
            $this->delete($replacePath, $disk);
        }

        return $to;
    }

    public function url(?string $path, string $disk = 'public'): ?string
    {
        if (! $path) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        $relative = $this->relativePath($path);

        return $relative ? Storage::disk($disk)->url($relative) : null;
    }

    private function storeAsWebp(UploadedFile $file, string $directory, string $disk, ?string $filename): ?string
    {
        if (! $this->webp->shouldConvertUploadedFile($file)) {
            return null;
        }

        $webp = $this->webp->encodeUploadedFile($file);
        if ($webp === null) {
            return null;
        }

        $storedName = $this->webp->toWebpFilename($filename, Str::uuid()->toString());
        $path = $directory.'/'.$storedName;
        Storage::disk($disk)->put($path, $webp);

        return $path;
    }

    private function webpPathForTarget(string $path): string
    {
        if (str_ends_with(strtolower($path), '.webp')) {
            return $path;
        }

        $directory = dirname($path);
        $filename = $this->webp->toWebpFilename(basename($path));

        return $directory === '.' ? $filename : $directory.'/'.$filename;
    }

    private function shouldConvertStoredFile(string $relativePath, string $disk): bool
    {
        if (str_ends_with(strtolower($relativePath), '.webp')) {
            return false;
        }

        if (! Storage::disk($disk)->exists($relativePath)) {
            return false;
        }

        return $this->webp->shouldConvertBinary(Storage::disk($disk)->get($relativePath));
    }
}
