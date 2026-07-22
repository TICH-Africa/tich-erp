<?php

namespace App\Models\Portal;

use Illuminate\Database\Eloquent\Model;

class ResearchProject extends Model
{
    protected $table = 'research_projects';

    public $timestamps = false;

    protected $fillable = [
        'title', 'subtitle', 'status', 'focus_area_id', 'summary', 'abstract',
        'cover_image_path', 'start_date', 'end_date', 'lead_researcher_id', 'is_featured',
    ];

    protected $casts = [
        'is_featured' => 'boolean',
        'start_date' => 'date',
        'end_date' => 'date',
    ];
}
