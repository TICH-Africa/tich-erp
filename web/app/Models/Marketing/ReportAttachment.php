<?php

namespace App\Models\Marketing;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportAttachment extends Model
{
    protected $table = 'marketing_report_attachments';

    protected $fillable = [
        'report_id', 'file_path', 'original_name', 'mime_type', 'size', 'uploaded_by',
    ];

    protected $casts = [
        'size' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class, 'report_id');
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Staff::class, 'uploaded_by');
    }

    public function url(): ?string
    {
        return \App\Support\PublicAsset::media($this->file_path);
    }
}