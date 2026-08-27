<?php

namespace App\Models\Portal;

use App\Models\Concerns\PrunesStoredFiles;
use Illuminate\Database\Eloquent\Model;

class BlogPost extends Model
{
    use PrunesStoredFiles;

    protected $table = 'blog_posts';

    /** @var array<string, string> */
    protected array $storedFiles = [
        'featured_image_path' => 'public',
    ];

    public $timestamps = false;

    protected $fillable = [
        'title', 'subtitle', 'slug', 'excerpt', 'body', 'featured_image_path',
        'author_staff_id', 'status', 'published_at', 'reading_time_minutes', 'view_count',
        'seo_meta_title', 'seo_meta_description',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
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
