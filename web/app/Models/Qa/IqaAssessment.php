<?php

namespace App\Models\Qa;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IqaAssessment extends Model
{
    protected $table = 'iqa_assessments';

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PUBLISHED = 'published';

    protected $fillable = [
        'title',
        'assessment_year',
        'status',
        'current_section',
        'payload',
        'created_by_user_id',
        'updated_by_user_id',
        'published_by_user_id',
        'publisher_name',
        'published_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'assessment_year' => 'integer',
        'current_section' => 'integer',
        'published_at' => 'datetime',
    ];

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_user_id');
    }

    public function publishedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by_user_id');
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED;
    }

    public function isEditable(): bool
    {
        return $this->isDraft();
    }
}
