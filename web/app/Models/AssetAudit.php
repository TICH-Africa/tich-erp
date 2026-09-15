<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetAudit extends Model
{
    protected $table = 'asset_audits';

    protected $fillable = [
        'asset_id',
        'auditor_id',
        'verification_status',
        'location_verified',
        'custodian_verified',
        'condition',
        'notes',
        'photo_paths',
        'submitted_at',
        'reviewed_by',
        'reviewed_at',
        'review_notes',
        'status',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'photo_paths' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function auditor(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'auditor_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'reviewed_by');
    }
}
