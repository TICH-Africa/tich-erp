<?php

namespace App\Models\Portal;

use App\Models\Concerns\PrunesStoredFiles;
use Illuminate\Database\Eloquent\Model;

class ResearchProject extends Model
{
    use PrunesStoredFiles;

    protected $table = 'research_projects';

    /** @var array<string, string> */
    protected array $storedFiles = [
        'cover_image_path' => 'public',
    ];

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
