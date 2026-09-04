<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentClearanceItem extends Model
{
    public const DEPARTMENTS = [
        'finance' => 'Finance',
        'library' => 'Library',
        'hostels' => 'Hostels',
        'academics' => 'Academic department',
        'registrar' => 'Registrar',
    ];

    protected $fillable = [
        'student_id',
        'department_key',
        'label',
        'status',
        'notes',
        'cleared_by_user_id',
        'cleared_at',
    ];

    protected $casts = [
        'cleared_at' => 'datetime',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
