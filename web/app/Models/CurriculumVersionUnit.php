<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CurriculumVersionUnit extends Model
{
    protected $table = 'curriculum_version_units';

    public $timestamps = false;

    protected $fillable = [
        'curriculum_version_id', 'unit_id', 'semester', 'block_id',
        'is_compulsory', 'display_order', 'priority',
        'credit_hours', 'contact_hours', 'total_learning_hours',
    ];

    protected $casts = [
        'credit_hours' => 'decimal:2',
        'is_compulsory' => 'boolean',
    ];

    public function version(): BelongsTo
    {
        return $this->belongsTo(CurriculumVersion::class, 'curriculum_version_id');
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
