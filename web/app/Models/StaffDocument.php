<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StaffDocument extends Model
{
    protected $table = 'staff_documents';

    protected $fillable = [
        'staff_id',
        'document_type',
        'document_name',
        'file_path',
        'original_filename',
        'mime_type',
        'file_size',
        'issue_date',
        'expiry_date',
        'status',
        'approved_by',
        'rejected_by',
        'approved_at',
        'rejected_at',
        'rejection_reason',
        'is_verified',
        'verified_by',
        'verified_at',
        'version',
        'replaced_by_id',
        'is_missing',
        'notes',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'expiry_date' => 'date',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'verified_at' => 'datetime',
        'is_verified' => 'boolean',
        'is_missing' => 'boolean',
        'file_size' => 'integer',
    ];

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'verified_by');
    }

    public function replacedBy(): BelongsTo
    {
        return $this->belongsTo(StaffDocument::class, 'replaced_by_id');
    }
}
