<?php

namespace App\Models;

use App\Models\Concerns\PrunesStoredFiles;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Student extends Model
{
    use PrunesStoredFiles;

    protected $table = 'students';

    /** @var array<string, string> */
    protected array $storedFiles = [
        'photo_path' => 'public',
    ];

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
        'academic_clearance_status',
        'academically_cleared_at',
        'academically_cleared_by',
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
        'academically_cleared_at' => 'datetime',
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

    public function suggestions(): HasMany
    {
        return $this->hasMany(StudentSuggestion::class);
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

    public function studentAccounts(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Models\Finance\StudentAccount::class);
    }

    public function fullName(): string
    {
        return $this->applicant?->fullName() ?? ($this->user?->name ?? 'N/A');
    }

    public function photoUrl(): ?string
    {
        if (! $this->photo_path) {
            return null;
        }

        return \Illuminate\Support\Facades\Storage::disk('public')->url($this->photo_path);
    }

    public function initials(): string
    {
        $this->loadMissing('applicant');

        $parts = array_filter([
            $this->applicant?->first_name,
            $this->applicant?->surname,
        ]);

        if ($parts === []) {
            return strtoupper(mb_substr((string) ($this->registration_number ?? 'S'), 0, 2));
        }

        return strtoupper(collect($parts)->map(fn ($part) => mb_substr((string) $part, 0, 1))->join(''));
    }
}
