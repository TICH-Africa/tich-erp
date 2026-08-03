<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StaffQualification extends Model
{
    protected $table = 'staff_qualifications';

    protected $fillable = [
        'staff_id',
        'qualification_type',
        'institution_name',
        'field_of_study',
        'grade',
        'start_date',
        'end_date',
        'certificate_path',
        'is_verified',
        'verified_by',
        'verified_at',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
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
