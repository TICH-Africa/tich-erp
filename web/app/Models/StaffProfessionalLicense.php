<?php

namespace App\Models;

use App\Models\Concerns\PrunesStoredFiles;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class StaffProfessionalLicense extends Model
{
    use HasFactory;
    use PrunesStoredFiles;

    /** @var array<string, string> */
    protected array $storedFiles = [
        'document_path' => 'public',
    ];

    protected $table = 'staff_professional_licenses';

    protected $fillable = [
        'staff_id',
        'license_type',
        'issuing_body',
        'license_number',
        'issue_date',
        'expiry_date',
        'is_expired',
        'days_to_expiry',
        'alert_sent_30_days',
        'alert_sent_60_days',
        'document_path',
        'is_verified',
        'verified_by',
        'verified_at',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'expiry_date' => 'date',
        'is_expired' => 'boolean',
        'days_to_expiry' => 'integer',
        'alert_sent_30_days' => 'boolean',
        'alert_sent_60_days' => 'boolean',
        'is_verified' => 'boolean',
        'verified_at' => 'datetime',
    ];

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'verified_by');
    }
}
