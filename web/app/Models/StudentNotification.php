<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentNotification extends Model
{
    protected $fillable = [
        'student_id',
        'category',
        'title',
        'body',
        'action_url',
        'read_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function isUnread(): bool
    {
        return $this->read_at === null;
    }
}
