<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\File\UploadedFile as SymfonyUploadedFile;

class ProgramFormCoverDraftService
{
    private const SESSION_ROOT = 'program_form_cover_drafts';

    public function scopeFromRequest(Request $request): string
    {
        $scope = (string) $request->input('_form', 'create');

        return in_array($scope, ['create', 'edit'], true) ? $scope : 'create';
    }

    /**
     * Keep the latest uploaded cover in session when the user corrects other fields.
     */
    public function rememberUpload(Request $request): void
    {
        $scope = $this->scopeFromRequest($request);

        if ($request->hasFile('cover_image')) {
            $this->forget($scope);
            $file = $request->file('cover_image');
            $token = Str::random(48);
            $directory = 'program-form-drafts/'.Str::slug((string) session()->getId(), '_');
            $storedPath = $file->storeAs($directory, $scope.'-'.$token.'.'.$file->getClientOriginalExtension(), 'local');

            session()->put($this->sessionKey($scope), [
                'token' => $token,
                'path' => $storedPath,
                'disk' => 'local',
            ]);

            return;
        }

        if ($this->hasRetainedCover($scope, $request->input('cover_image_draft'))) {
            return;
        }
    }

    public function hasRetainedCover(string $scope, ?string $token): bool
    {
        if (! $token) {
            return false;
        }

        $draft = session($this->sessionKey($scope));

        return is_array($draft) && ($draft['token'] ?? null) === $token && ! empty($draft['path']);
    }

    public function currentToken(string $scope): ?string
    {
        $draft = session($this->sessionKey($scope));

        return is_array($draft) ? ($draft['token'] ?? null) : null;
    }

    public function previewAbsolutePath(string $scope, ?string $token): ?string
    {
        if (! $this->hasRetainedCover($scope, $token)) {
            return null;
        }

        $draft = session($this->sessionKey($scope));
        $disk = (string) ($draft['disk'] ?? 'local');
        $path = (string) ($draft['path'] ?? '');

        if ($path === '' || ! Storage::disk($disk)->exists($path)) {
            return null;
        }

        return Storage::disk($disk)->path($path);
    }

    public function resolveUploadedFile(Request $request): UploadedFile
    {
        if ($request->hasFile('cover_image')) {
            return $request->file('cover_image');
        }

        $scope = $this->scopeFromRequest($request);
        $token = (string) $request->input('cover_image_draft', '');
        $absolute = $this->previewAbsolutePath($scope, $token);

        if (! $absolute || ! is_readable($absolute)) {
            throw new \InvalidArgumentException('Cover image is required.');
        }

        $draft = session($this->sessionKey($scope));
        $originalName = basename((string) ($draft['path'] ?? 'cover.jpg'));

        return UploadedFile::createFromBase(
            new SymfonyUploadedFile($absolute, $originalName, mime_content_type($absolute) ?: null, null, true),
        );
    }

    public function forget(string $scope): void
    {
        $draft = session($this->sessionKey($scope));
        if (is_array($draft) && ! empty($draft['path'])) {
            Storage::disk((string) ($draft['disk'] ?? 'local'))->delete((string) $draft['path']);
        }

        session()->forget($this->sessionKey($scope));
    }

    private function sessionKey(string $scope): string
    {
        return self::SESSION_ROOT.'.'.$scope;
    }
}
