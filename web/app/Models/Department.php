<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Department extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'dept_code', 'dept_name', 'dept_category', 'hod_id', 'parent_dept_id',
        'campus_id', 'is_active', 'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function campus(): BelongsTo
    {
        return $this->belongsTo(Campus::class);
    }
}
