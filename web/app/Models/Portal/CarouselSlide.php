<?php

namespace App\Models\Portal;

use Illuminate\Database\Eloquent\Model;

class CarouselSlide extends Model
{
    protected $table = 'homepage_carousel_slides';

    protected $fillable = [
        'title', 'subtitle', 'image_path', 'video_url',
        'cta_label', 'cta_url', 'display_order', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
