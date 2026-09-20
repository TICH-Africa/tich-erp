<?php

namespace App\Models\Portal;

use App\Models\Concerns\PrunesStoredFiles;
use App\Models\Staff;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PartnershipRequest extends Model
{
    use PrunesStoredFiles;

    protected $table = 'partnership_requests';

    public $timestamps = false;

    /** @var array<string, string> */
    protected array $storedFiles = [
        'supporting_document_path' => 'local',
    ];

    protected $fillable = [
        'request_number', 'applicant_type', 'first_name', 'last_name',
        'organization_name', 'organisation_details', 'individual_details', 'organization_type',
        'contact_person', 'email', 'alternative_email', 'phone', 'alternative_phone',
        'research_area', 'what_they_do', 'why_partnership', 'proposed_scope',
        'target_sub_counties', 'supporting_document_path', 'status',
        'reviewed_by', 'reviewed_at', 'review_notes', 'alert_sent_to_registrar',
        'created_at', 'created_by', 'updated_at', 'updated_by',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'alert_sent_to_registrar' => 'boolean',
    ];

    public function documents(): HasMany
    {
        return $this->hasMany(PartnershipRequestDocument::class, 'partnership_request_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'reviewed_by');
    }

    public function displayName(): string
    {
        if ($this->applicant_type === 'individual') {
            return trim(($this->first_name ?? '').' '.($this->last_name ?? '')) ?: ($this->contact_person ?: 'Individual');
        }

        return $this->organization_name ?: trim(($this->first_name ?? '').' '.($this->last_name ?? '')) ?: 'Organisation';
    }
}
