<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AcademicYear extends Model
{
    protected $table = 'academic_years';

    public $timestamps = false;

    protected $fillable = [
        'year_label', 'start_date', 'end_date', 'is_current', 'created_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_current' => 'boolean',
        'created_at' => 'datetime',
    ];

    public function semesters(): HasMany
    {
        return $this->hasMany(Semester::class, 'academic_year_id')->orderBy('semester_number');
    }
}
