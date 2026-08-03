<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StaffDisciplinaryCase extends Model
{
    protected $table = 'staff_disciplinary_cases';

    protected $fillable = [
        'staff_id',
        'case_number',
        'case_type',
        'description',
        'status',
        'decision',
        'penalty',
        'hearing_date',
        'decision_date',
        'handled_by',
        'created_by',
    ];

    protected $casts = [
        'hearing_date' => 'date',
        'decision_date' => 'date',
    ];

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    public function handledBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'handled_by');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'created_by');
    }
}
