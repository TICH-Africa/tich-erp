<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffQualification extends Model
{
    public $timestamps = false;

    protected $table = 'staff_qualifications';

    protected $fillable = [
        'staff_id',
        'qualification_type',
        'qualification_name',
        'institution',
        'country',
        'year_completed',
        'grade_or_class',
        'certificate_number',
        'document_path',
        'is_verified',
        'verified_by',
        'verified_at',
    ];

    protected $casts = [
        'year_completed' => 'integer',
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
