<?php

namespace App\Models\Marketing;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lead extends Model
{
    protected $table = 'marketing_leads';

    protected $fillable = [
        'name', 'email', 'phone', 'source', 'stage', 'program_id', 'intake',
        'notes', 'next_followup', 'assigned_to', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'next_followup' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function program(): BelongsTo
    {
        return $this->belongsTo(\App\Models\AcademicProgram::class, 'program_id');
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Staff::class, 'assigned_to');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Staff::class, 'created_by');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(\App\Models\Marketing\LeadActivity::class, 'lead_id');
    }
}
