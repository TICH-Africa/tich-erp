<?php

namespace App\Models\Site;

use Illuminate\Database\Eloquent\Model;

class SocialLink extends Model
{
    protected $table = 'social_links';

    public $timestamps = false;

    protected $fillable = [
        'platform', 'display_name', 'url', 'icon_name', 'display_order', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
