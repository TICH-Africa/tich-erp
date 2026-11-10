<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DataSubjectRequest extends Model
{
    protected $table = 'data_subject_requests';

    protected $fillable = [
        'request_number',
        'requester_name',
        'requester_email',
        'requester_phone',
        'subject_type',
        'subject_id',
        'request_type',
        'description',
        'status',
        'received_at',
        'due_at',
        'completed_at',
        'handled_by',
        'outcome_notes',
    ];

    protected $casts = [
        'received_at' => 'datetime',
        'due_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function handler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    public static function requestTypeOptions(): array
    {
        return [
            'access' => 'Access',
            'rectification' => 'Rectification',
            'erasure' => 'Erasure',
            'objection' => 'Objection',
            'complaint' => 'Complaint',
        ];
    }

    public function requestTypeLabel(): string
    {
        return self::requestTypeOptions()[$this->request_type] ?? ucfirst($this->request_type ?? 'Unknown');
    }
}
