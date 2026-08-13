<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffDocumentTemplate extends Model
{
    protected $table = 'staff_document_templates';

    protected $fillable = [
        'name',
        'type',
        'content',
        'format',
        'variables',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'variables' => 'array',
        'is_active' => 'boolean',
    ];

    protected $attributes = [
        'variables' => '[]',
        'is_active' => true,
        'format' => 'html',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'created_by');
    }
}
