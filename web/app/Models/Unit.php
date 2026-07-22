<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Unit extends Model
{
    protected $table = 'units';

    public $timestamps = false;

    protected $fillable = [
        'unit_code', 'unit_name', 'description', 'department_id', 'program_id',
        'semester', 'block', 'credit_hours', 'contact_hours', 'total_learning_hours',
        'display_priority', 'is_core', 'is_practical',
        'prerequisite_unit_id', 'co_requisite_unit_id',
        'assessment_weight_attendance_pct', 'assessment_weight_cat_pct',
        'assessment_weight_practical_pct', 'assessment_weight_exam_pct',
        'status', 'submitted_at', 'submitted_by',
        'registrar_approved_at', 'registrar_approved_by', 'created_by',
    ];

    protected $casts = [
        'credit_hours' => 'decimal:2',
        'is_core' => 'boolean',
        'is_practical' => 'boolean',
        'submitted_at' => 'datetime',
        'registrar_approved_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(AcademicProgram::class, 'program_id');
    }

    public function prerequisite(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'prerequisite_unit_id');
    }

    public function coRequisite(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'co_requisite_unit_id');
    }

    public function programLinks(): HasMany
    {
        return $this->hasMany(ProgramUnit::class, 'unit_id');
    }

    public function programs(): BelongsToMany
    {
        return $this->belongsToMany(AcademicProgram::class, 'program_units', 'unit_id', 'program_id')
            ->withPivot(['semester', 'block_id', 'is_compulsory', 'display_order', 'priority', 'contact_hours', 'total_learning_hours']);
    }

    public function isEditable(): bool
    {
        return in_array($this->status, ['draft', 'pending_registry'], true);
    }
}
