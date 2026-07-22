<?php

namespace App\Models\Portal;

use Illuminate\Database\Eloquent\Model;

class BlogPost extends Model
{
    protected $table = 'blog_posts';

    public $timestamps = false;

    protected $fillable = [
        'title', 'subtitle', 'slug', 'excerpt', 'body', 'featured_image_path',
        'author_staff_id', 'status', 'published_at', 'reading_time_minutes', 'view_count',
    ];

    protected $casts = [
        'published_at' => 'datetime',
    ];
}
