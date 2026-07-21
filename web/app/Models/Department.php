<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'dept_code', 'dept_name', 'dept_category', 'department_group_id', 'display_order',
        'hod_id', 'parent_dept_id', 'campus_id', 'is_active', 'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function campus(): BelongsTo
    {
        return $this->belongsTo(Campus::class);
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(DepartmentGroup::class, 'department_group_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'parent_dept_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Department::class, 'parent_dept_id')->orderBy('display_order')->orderBy('dept_name');
    }

    public function programs(): HasMany
    {
        return $this->hasMany(AcademicProgram::class, 'department_id');
    }

    public function isLearningDepartment(): bool
    {
        return $this->dept_category === 'academic' && $this->parent_dept_id !== null;
    }
}
