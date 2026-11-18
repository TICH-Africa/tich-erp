<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class EfundiOpportunity extends Model
{
    public const TYPE_JOB = 'job';

    public const TYPE_INTERNSHIP = 'internship';

    public const TYPE_SCHOLARSHIP = 'scholarship';

    public const TYPE_OTHER = 'other';

    protected $fillable = [
        'title',
        'slug',
        'opportunity_type',
        'organization_name',
        'location',
        'summary',
        'description',
        'application_url',
        'application_email',
        'application_deadline',
        'is_published',
        'published_at',
        'posted_via',
        'posted_by_user_id',
        'students_notified_at',
    ];

    protected $casts = [
        'application_deadline' => 'date',
        'is_published' => 'boolean',
        'published_at' => 'datetime',
        'students_notified_at' => 'datetime',
    ];

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by_user_id');
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function typeLabel(): string
    {
        return match ($this->opportunity_type) {
            self::TYPE_JOB => 'Job',
            self::TYPE_INTERNSHIP => 'Internship',
            self::TYPE_SCHOLARSHIP => 'Scholarship',
            default => 'Opportunity',
        };
    }

    public static function types(): array
    {
        return [
            self::TYPE_JOB => 'Job',
            self::TYPE_INTERNSHIP => 'Internship',
            self::TYPE_SCHOLARSHIP => 'Scholarship',
            self::TYPE_OTHER => 'Other',
        ];
    }

    public function isOpen(): bool
    {
        if (! $this->is_published) {
            return false;
        }

        if ($this->application_deadline === null) {
            return true;
        }

        return $this->application_deadline->gte(now()->startOfDay());
    }

    public static function uniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title);
        if ($base === '') {
            $base = 'opportunity';
        }

        $slug = $base;
        $n = 1;

        while (static::query()
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->where('slug', $slug)
            ->exists()) {
            $slug = $base.'-'.$n;
            $n++;
        }

        return $slug;
    }
}