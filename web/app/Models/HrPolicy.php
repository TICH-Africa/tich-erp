<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HrPolicy extends Model
{
    protected $table = 'hr_policies';

    protected $fillable = [
        'title',
        'slug',
        'description',
        'file_path',
        'original_filename',
        'mime_type',
        'file_size',
        'category',
        'effective_date',
        'expiry_date',
        'is_active',
        'tags',
        'uploaded_by',
    ];

    protected $casts = [
        'effective_date' => 'date',
        'expiry_date' => 'date',
        'is_active' => 'boolean',
    ];

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'uploaded_by');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', 1);
    }
}
