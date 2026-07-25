<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Staff extends Model
{
    protected $table = 'staff';

    public $timestamps = false;

    protected $fillable = [
        'employee_number',
        'first_name',
        'middle_name',
        'surname',
        'email',
        'department_id',
        'job_title',
        'employment_status',
        'is_teaching_staff',
        'user_id',
    ];

    protected $casts = [
        'is_teaching_staff' => 'boolean',
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function unitAllocations(): HasMany
    {
        return $this->hasMany(UnitAllocation::class);
    }

    public function fullName(): string
    {
        return trim(implode(' ', array_filter([$this->first_name, $this->middle_name, $this->surname])));
    }
}
