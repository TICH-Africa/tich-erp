<?php

namespace App\Models\Concerns;

use App\Services\StoredFileService;

/**
 * Deletes stored files when path attributes change or the model is removed.
 *
 * Define {@see $storedFiles} as attribute => disk, and optionally {@see $storedFileArrays}
 * for JSON/list columns that hold multiple paths.
 */
trait PrunesStoredFiles
{
    public static function bootPrunesStoredFiles(): void
    {
        static::updating(function (self $model) {
            $service = app(StoredFileService::class);

            foreach ($model->storedFileAttributes() as $attribute => $disk) {
                if (! $model->isDirty($attribute)) {
                    continue;
                }

                $old = $model->getOriginal($attribute);
                $new = $model->{$attribute};

                if ($old && ! $service->pathsMatch($old, $new, $disk)) {
                    $service->delete($old, $disk);
                }
            }

            foreach ($model->storedFileArrayAttributes() as $attribute => $disk) {
                if (! $model->isDirty($attribute)) {
                    continue;
                }

                $oldPaths = $model->normalizeStoredPaths($model->getOriginal($attribute));
                $newPaths = $model->normalizeStoredPaths($model->{$attribute});

                foreach (array_diff($oldPaths, $newPaths) as $path) {
                    $service->delete($path, $disk);
                }
            }
        });

        static::deleting(function (self $model) {
            $service = app(StoredFileService::class);

            foreach ($model->storedFileAttributes() as $attribute => $disk) {
                if ($path = $model->{$attribute}) {
                    $service->delete($path, $disk);
                }
            }

            foreach ($model->storedFileArrayAttributes() as $attribute => $disk) {
                foreach ($model->normalizeStoredPaths($model->{$attribute}) as $path) {
                    $service->delete($path, $disk);
                }
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function storedFileAttributes(): array
    {
        return property_exists($this, 'storedFiles') && is_array($this->storedFiles)
            ? $this->storedFiles
            : [];
    }

    /**
     * @return array<string, string>
     */
    protected function storedFileArrayAttributes(): array
    {
        return property_exists($this, 'storedFileArrays') && is_array($this->storedFileArrays)
            ? $this->storedFileArrays
            : [];
    }

    /**
     * @return list<string>
     */
    protected function normalizeStoredPaths(mixed $value): array
    {
        if (is_string($value) && $value !== '') {
            return [$value];
        }

        if (! is_array($value)) {
            return [];
        }

        return array_values(array_filter($value, fn ($path) => is_string($path) && $path !== ''));
    }
}
