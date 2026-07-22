<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProgramUnit extends Model
{
    protected $table = 'program_units';

    public $timestamps = false;

    protected $fillable = [
        'program_id', 'unit_id', 'semester', 'block_id',
        'is_compulsory', 'display_order', 'priority',
        'contact_hours', 'total_learning_hours', 'is_active',
    ];

    protected $casts = [
        'is_compulsory' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function program(): BelongsTo
    {
        return $this->belongsTo(AcademicProgram::class, 'program_id');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function block(): BelongsTo
    {
        return $this->belongsTo(NursingBlock::class, 'block_id');
    }
}
