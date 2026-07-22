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

    public function isMainDepartment(): bool
    {
        return $this->parent_dept_id === null;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeMain($query)
    {
        return $query->whereNull('parent_dept_id');
    }

    public function resolveRootId(): int
    {
        $current = $this;

        while ($current->parent_dept_id !== null) {
            $parent = static::query()->find($current->parent_dept_id);

            if (! $parent) {
                break;
            }

            $current = $parent;
        }

        return (int) $current->id;
    }

    public static function parentMap(): array
    {
        return static::query()->pluck('parent_dept_id', 'id')->all();
    }

    public static function resolveRootIdFromMap(int $departmentId, array $parentMap): int
    {
        $current = $departmentId;

        while (! empty($parentMap[$current])) {
            $current = (int) $parentMap[$current];
        }

        return $current;
    }

    /**
     * @return list<int> Department id plus all descendant ids.
     */
    public function selfAndDescendantIds(): array
    {
        $ids = [(int) $this->id];
        $children = static::query()
            ->where('parent_dept_id', $this->id)
            ->pluck('id');

        foreach ($children as $childId) {
            $ids = array_merge($ids, static::find($childId)?->selfAndDescendantIds() ?? []);
        }

        return array_values(array_unique($ids));
    }
}
