<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class JobVacancy extends Model
{
    use HasFactory;

    protected $table = 'job_vacancies';

    public $timestamps = false;

    protected $fillable = [
        'vacancy_number',
        'job_title',
        'department_id',
        'employment_type',
        'position_grade',
        'slots_available',
        'job_description',
        'requirements',
        'responsibilities',
        'salary_scale',
        'benefits',
        'min_qualification',
        'closing_date',
        'is_published',
        'published_on',
        'is_closed',
        'closes_automatically',
        'slots_filled',
        'created_by',
    ];

    protected $casts = [
        'closing_date' => 'date',
        'published_on' => 'date',
        'is_published' => 'boolean',
        'is_closed' => 'boolean',
        'closes_automatically' => 'boolean',
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'created_by');
    }

    public function applications(): HasMany
    {
        return $this->hasMany(RecruitmentApplication::class, 'vacancy_id');
    }
}
