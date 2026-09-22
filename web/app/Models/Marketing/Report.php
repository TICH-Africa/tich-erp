<?php

namespace App\Models\Marketing;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Report extends Model
{
    protected $table = 'marketing_reports';

    protected $fillable = [
        'report_type', 'title', 'report_date', 'summary', 'commentary', 'anomalies',
        'status', 'prepared_by', 'reviewed_by', 'approved_by', 'distributed_by',
        'distribution_list', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'report_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function preparedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Staff::class, 'prepared_by');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Staff::class, 'reviewed_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Staff::class, 'approved_by');
    }

    public function distributedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Staff::class, 'distributed_by');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(\App\Models\Marketing\ReportAttachment::class, 'report_id');
    }
}
