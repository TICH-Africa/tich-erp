<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RecruitmentApplication extends Model
{
    use HasFactory;

    protected $table = 'recruitment_applications';

    protected $fillable = [
        'application_number',
        'vacancy_id',
        'full_name',
        'id_number',
        'date_of_birth',
        'gender',
        'marital_status',
        'email',
        'phone_number',
        'postal_address',
        'physical_address',
        'highest_qualification',
        'qualification_other',
        'institution',
        'year_completed',
        'grade',
        'years_of_experience',
        'current_organization',
        'area_of_specialization',
        'cv_file_path',
        'cover_letter_file_path',
        'certificates_file_paths',
        'referee1_name',
        'referee1_title',
        'referee1_organization',
        'referee1_contact',
        'referee2_name',
        'referee2_title',
        'referee2_organization',
        'referee2_contact',
        'expected_salary',
        'notice_period',
        'is_shortlisted',
        'shortlist_status',
        'interview_date',
        'interview_panel_ids',
        'interview_score',
        'interview_notes',
        'offer_made',
        'offer_accepted',
        'new_staff_id',
        'is_onboarded',
        'rejection_reason',
        'application_source',
        'status',
        'reviewed_by',
        'reviewed_at',
        'decision',
        'decision_notes',
        'is_viewed',
    ];

    protected $casts = [
        'is_shortlisted' => 'boolean',
        'offer_made' => 'boolean',
        'offer_accepted' => 'boolean',
        'is_onboarded' => 'boolean',
        'interview_date' => 'datetime',
        'interview_panel_ids' => 'array',
        'certificates_file_paths' => 'array',
        'interview_score' => 'decimal:2',
        'date_of_birth' => 'date',
        'reviewed_at' => 'datetime',
        'is_viewed' => 'boolean',
        'year_completed' => 'integer',
        'years_of_experience' => 'integer',
    ];

    public function vacancy(): BelongsTo
    {
        return $this->belongsTo(JobVacancy::class);
    }

    public function newStaff(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'new_staff_id');
    }
}
