<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class MobileAppRelease extends Model
{
    public const PLATFORM_ANDROID = 'android';

    public const PLATFORM_IOS = 'ios';

    protected $fillable = [
        'platform',
        'version_label',
        'build_number',
        'storage_path',
        'original_filename',
        'file_size',
        'external_url',
        'release_notes',
        'is_published',
        'uploaded_by_user_id',
    ];

    protected $casts = [
        'build_number' => 'integer',
        'file_size' => 'integer',
        'is_published' => 'boolean',
    ];

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_user_id');
    }

    public function hasDownloadableFile(): bool
    {
        if (! $this->storage_path) {
            return false;
        }

        return Storage::disk('local')->exists($this->storage_path);
    }

    public function downloadFilename(): string
    {
        if ($this->platform === self::PLATFORM_ANDROID) {
            $name = 'tich-mobile-'.$this->version_label;
            if ($this->build_number) {
                $name .= '-build'.$this->build_number;
            }

            return $name.'-android.apk';
        }

        if ($this->original_filename && str_ends_with(strtolower($this->original_filename), '.ipa')) {
            return $this->original_filename;
        }

        $name = 'tich-mobile-'.$this->version_label;
        if ($this->build_number) {
            $name .= '-build'.$this->build_number;
        }

        return $name.'-ios.ipa';
    }

    public function formattedFileSize(): ?string
    {
        if ($this->file_size === null || $this->file_size <= 0) {
            return null;
        }

        $bytes = (float) $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, $i > 0 ? 1 : 0).' '.$units[$i];
    }

    public static function currentForPlatform(string $platform): ?self
    {
        return static::query()
            ->where('platform', $platform)
            ->where('is_published', true)
            ->orderByDesc('created_at')
            ->first();
    }
}