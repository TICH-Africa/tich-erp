<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Student extends Model
{
    protected $table = 'students';

    public $timestamps = false;

    protected $fillable = [
        'registration_number',
        'application_id',
        'program_id',
        'cohort_intake',
        'enrollment_campus_id',
        'current_semester_id',
        'current_nursing_block_id',
        'enrollment_status',
        'entry_pathway',
        'admission_letter_id',
        'photo_path',
        'date_of_admission',
        'is_nursing_student',
        'kcse_english_grade',
        'kcse_biology_grade',
        'kcse_science_grade',
        'fee_clearance_status',
        'overall_balance',
        'user_id',
        'portal_invite_token',
        'portal_invite_expires_at',
        'portal_activated_at',
        'is_hostel_seeker',
        'hostel_allocation_id',
        'emergency_contact_name',
        'emergency_contact_phone',
        'emergency_contact_relationship',
        'is_active',
        'created_at',
        'updated_at',
        'created_by',
    ];

    protected $casts = [
        'date_of_admission' => 'date',
        'portal_invite_expires_at' => 'datetime',
        'portal_activated_at' => 'datetime',
        'overall_balance' => 'decimal:2',
        'is_nursing_student' => 'boolean',
        'is_hostel_seeker' => 'boolean',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function applicant(): BelongsTo
    {
        return $this->belongsTo(Applicant::class, 'application_id');
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(AcademicProgram::class, 'program_id');
    }

    public function campus(): BelongsTo
    {
        return $this->belongsTo(Campus::class, 'enrollment_campus_id');
    }

    public function accounts(): HasMany
    {
        return $this->hasMany(StudentAccount::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function financePayments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function displayName(): string
    {
        $this->loadMissing('applicant');

        if ($this->applicant) {
            return trim($this->applicant->first_name.' '.$this->applicant->surname);
        }

        return $this->registration_number ?? 'Student #'.$this->id;
    }

    public function hasActivePortalInvite(): bool
    {
        if ($this->portal_activated_at !== null || $this->user_id !== null) {
            return false;
        }

        return $this->portal_invite_token !== null
            && ($this->portal_invite_expires_at === null || $this->portal_invite_expires_at->isFuture());
    }

    public function portalActivationUrl(): ?string
    {
        if (! $this->hasActivePortalInvite()) {
            return null;
        }

        return route('portal.activate', ['token' => $this->portal_invite_token]);
    }
}
