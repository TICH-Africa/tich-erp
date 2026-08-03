<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProfessionalDevelopment extends Model
{
    protected $table = 'professional_development';

    protected $fillable = [
        'staff_id',
        'training_type',
        'training_name',
        'provider',
        'start_date',
        'end_date',
        'duration_hours',
        'cost',
        'certificate_path',
        'is_completed',
        'completed_at',
        'created_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'completed_at' => 'date',
        'cost' => 'decimal:2',
        'duration_hours' => 'integer',
        'is_completed' => 'boolean',
    ];

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'created_by');
    }
}
