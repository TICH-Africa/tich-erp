<?php

namespace App\Models\Portal;

use App\Models\Concerns\PrunesStoredFiles;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use PrunesStoredFiles;

    protected $table = 'events';

    /** @var array<string, string> */
    protected array $storedFiles = [
        'cover_image_path' => 'public',
    ];

    public $timestamps = false;

    protected $fillable = [
        'title', 'subtitle', 'event_type', 'description', 'cover_image_path',
        'start_datetime', 'end_datetime', 'venue', 'registration_url_or_form',
        'is_public', 'is_featured',
    ];

    protected $casts = [
        'start_datetime' => 'datetime',
        'end_datetime' => 'datetime',
        'is_public' => 'boolean',
        'is_featured' => 'boolean',
    ];

    public function coverImageUrl(): ?string
    {
        if (! $this->cover_image_path) {
            return null;
        }

        if (str_starts_with($this->cover_image_path, 'http://') || str_starts_with($this->cover_image_path, 'https://')) {
            return $this->cover_image_path;
        }

        return asset(ltrim($this->cover_image_path, '/'));
    }
}
