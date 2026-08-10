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
    ];

    protected $casts = [
        'published_at' => 'datetime',
    ];
}
