<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NursingBlock extends Model
{
    protected $table = 'nursing_blocks';

    public $timestamps = false;

    protected $fillable = [
        'block_label', 'block_order', 'duration_months', 'program_id', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime',
    ];

    public function program(): BelongsTo
    {
        return $this->belongsTo(AcademicProgram::class, 'program_id');
    }

    public function programUnits(): HasMany
    {
        return $this->hasMany(ProgramUnit::class, 'block_id');
    }
}
