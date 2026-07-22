<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AcademicProgram extends Model
{
    protected $table = 'academic_programs';

    public $timestamps = false;

    protected $fillable = [
        'program_code', 'program_name', 'program_type', 'regulatory_body',
        'department_id', 'duration_months', 'status',
        'is_featured_on_homepage', 'homepage_display_order',
        'homepage_tagline', 'entry_requirements', 'created_by',
    ];

    protected $casts = [
        'is_featured_on_homepage' => 'boolean',
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }
}
