<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentSuggestion extends Model
{
    public const CATEGORIES = [
        'suggestion' => 'Suggestion',
        'comment' => 'Comment',
        'complaint' => 'Complaint',
    ];

    public const STATUSES = [
        'open' => 'Open',
        'under_review' => 'Under review',
        'resolved' => 'Resolved',
        'closed' => 'Closed',
    ];

    protected $table = 'student_suggestions';

    protected $fillable = [
        'student_id',
        'category',
        'subject',
        'body',
        'status',
        'response',
        'reviewed_by',
        'resolved_at',
        'metadata',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function categoryLabel(): string
    {
        return self::CATEGORIES[$this->category] ?? ucfirst((string) $this->category);
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst(str_replace('_', ' ', (string) $this->status));
    }

    public function statusBadge(): string
    {
        return match ($this->status) {
            'open' => 'warning',
            'under_review' => 'info',
            'resolved' => 'success',
            'closed' => 'secondary',
            default => 'secondary',
        };
    }
}
