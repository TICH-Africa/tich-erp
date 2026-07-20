<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Applicant extends Model
{
    protected $table = 'applicants';

    public $timestamps = false;

    protected $fillable = [
        'application_number',
        'program_id',
        'preferred_campus_id',
        'first_name',
        'middle_name',
        'surname',
        'date_of_birth',
        'gender',
        'national_id_number',
        'passport_number',
        'email',
        'phone_number',
        'home_county',
        'entry_qualification',
        'application_fee_paid',
        'status',
        'academic_review_status',
        'application_source',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'application_fee_paid' => 'boolean',
        'created_at' => 'datetime',
    ];

    public function program(): BelongsTo
    {
        return $this->belongsTo(AcademicProgram::class, 'program_id');
    }

    public function preferredCampus(): BelongsTo
    {
        return $this->belongsTo(Campus::class, 'preferred_campus_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(ApplicationDocument::class, 'applicant_id');
    }
}
