<?php

namespace App\Models\Portal;

use App\Models\Concerns\PrunesStoredFiles;
use Illuminate\Database\Eloquent\Model;

class CarouselSlide extends Model
{
    use PrunesStoredFiles;

    protected $table = 'homepage_carousel_slides';

    /** @var array<string, string> */
    protected array $storedFiles = [
        'image_path' => 'public',
    ];

    protected $fillable = [
        'program_id',
        'title', 'subtitle', 'image_path', 'video_url',
        'cta_label', 'cta_url', 'display_order', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function program(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\AcademicProgram::class, 'program_id');
    }
}
