<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DisciplinaryCorrespondence extends Model
{
    protected $fillable = [
        'disciplinary_case_id',
        'recipient_staff_id',
        'sent_by_user_id',
        'subject',
        'body',
        'emailed_at',
        'notified_at',
    ];

    protected $casts = [
        'emailed_at' => 'datetime',
        'notified_at' => 'datetime',
    ];

    public function case(): BelongsTo
    {
        return $this->belongsTo(DisciplinaryCase::class, 'disciplinary_case_id');
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'recipient_staff_id');
    }

    public function sentBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by_user_id');
    }
}
