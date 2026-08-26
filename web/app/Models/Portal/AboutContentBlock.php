<?php

namespace App\Models\Portal;

use App\Models\Concerns\PrunesStoredFiles;
use Illuminate\Database\Eloquent\Model;

class AboutContentBlock extends Model
{
    use PrunesStoredFiles;

    protected $table = 'about_content_blocks';

    /** @var array<string, string> */
    protected array $storedFiles = [
        'featured_image_path' => 'public',
    ];

    public $timestamps = false;

    protected $fillable = [
        'block_key',
        'title',
        'subtitle',
        'body',
        'featured_image_path',
        'display_order',
        'is_active',
        'created_by',
        'updated_by',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'display_order' => 'integer',
    ];

    public function imageUrl(): ?string
    {
        if (! $this->featured_image_path) {
            return null;
        }

        if (str_starts_with($this->featured_image_path, 'http://')
            || str_starts_with($this->featured_image_path, 'https://')) {
            return $this->featured_image_path;
        }

        return asset(ltrim($this->featured_image_path, '/'));
    }
}
