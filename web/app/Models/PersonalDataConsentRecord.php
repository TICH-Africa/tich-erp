<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PersonalDataConsentRecord extends Model
{
    public $timestamps = false;

    protected $table = 'personal_data_consent_records';

    protected $fillable = [
        'subject_type',
        'subject_id',
        'subject_email',
        'purpose_key',
        'lawful_basis',
        'privacy_notice_version',
        'terms_version',
        'consented',
        'consent_method',
        'ip_address',
        'user_agent',
        'channel',
        'consented_at',
        'withdrawn_at',
    ];

    protected $casts = [
        'consented' => 'boolean',
        'consented_at' => 'datetime',
        'withdrawn_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function applicant(): BelongsTo
    {
        return $this->belongsTo(Applicant::class, 'subject_id');
    }
}
