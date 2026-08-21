<?php

namespace App\Services\Administration;

use App\Services\SiteSettingsService;
use App\Services\StoredFileService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class AdmissionLetterTemplateService
{
    public const PATH_KEY = 'admissions.admission_letter_path';

    public const NAME_KEY = 'admissions.admission_letter_filename';

    public function __construct(
        protected SiteSettingsService $settings,
        protected StoredFileService $files,
    ) {}

    public function path(): ?string
    {
        $path = $this->settings->get(self::PATH_KEY);

        return filled($path) ? $path : null;
    }

    public function originalFilename(): ?string
    {
        $name = $this->settings->get(self::NAME_KEY);

        return filled($name) ? $name : null;
    }

    public function exists(): bool
    {
        $path = $this->path();
        $relative = $path ? $this->files->relativePath($path) : null;

        return $relative !== null && Storage::disk('public')->exists($relative);
    }

    /**
     * @return array{path: string, filename: string}|null
     */
    public function attachment(): ?array
    {
        if (! $this->exists()) {
            return null;
        }

        $path = $this->path();
        $relative = $this->files->relativePath($path);

        return [
            'path' => $relative,
            'filename' => $this->originalFilename() ?: basename((string) $relative),
        ];
    }

    public function store(UploadedFile $file, ?int $userId = null): string
    {
        $oldPath = $this->path();

        $stored = $this->files->replace(
            $oldPath,
            $file,
            'administration/admission-letters',
            'public',
            time().'_'.$file->getClientOriginalName()
        );

        $this->settings->set(self::PATH_KEY, $stored, $userId, [
            'value_type' => 'file',
            'group_name' => 'admissions',
            'label' => 'Admission letter template',
            'description' => 'Uploaded admission letter attached when applications are admitted.',
            'is_public' => 0,
        ]);

        $this->settings->set(self::NAME_KEY, $file->getClientOriginalName(), $userId, [
            'value_type' => 'string',
            'group_name' => 'admissions',
            'label' => 'Admission letter filename',
            'is_public' => 0,
        ]);

        return $stored;
    }

    public function clear(?int $userId = null): void
    {
        $this->files->delete($this->path());
        $this->settings->set(self::PATH_KEY, null, $userId, [
            'value_type' => 'file',
            'group_name' => 'admissions',
            'label' => 'Admission letter template',
            'is_public' => 0,
        ]);
        $this->settings->set(self::NAME_KEY, null, $userId, [
            'value_type' => 'string',
            'group_name' => 'admissions',
            'label' => 'Admission letter filename',
            'is_public' => 0,
        ]);
    }
}
