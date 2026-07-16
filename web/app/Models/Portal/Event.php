<?php

namespace App\Models\Portal;

use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    protected $table = 'events';

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
}
