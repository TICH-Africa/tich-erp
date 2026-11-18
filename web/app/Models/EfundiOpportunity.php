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

    public const SCOPE_EXTERNAL = 'external';

    public const SCOPE_INTERNAL = 'internal';

    protected $fillable = [
        'title',
        'slug',
        'opportunity_type',
        'opportunity_scope',
        'job_vacancy_id',
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

    public function jobVacancy(): BelongsTo
    {
        return $this->belongsTo(JobVacancy::class, 'job_vacancy_id');
    }

    public function isInternal(): bool
    {
        return ($this->opportunity_scope ?? self::SCOPE_EXTERNAL) === self::SCOPE_INTERNAL;
    }

    public function isExternal(): bool
    {
        return ! $this->isInternal();
    }

    public static function scopes(): array
    {
        return [
            self::SCOPE_EXTERNAL => 'External',
            self::SCOPE_INTERNAL => 'Within TICH',
        ];
    }

    public function scopeLabel(): string
    {
        return self::scopes()[$this->opportunity_scope] ?? 'External';
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

        if ($this->isInternal()) {
            $vacancy = $this->relationLoaded('jobVacancy') ? $this->jobVacancy : $this->jobVacancy()->first();

            return $vacancy && self::vacancyAcceptsApplications($vacancy);
        }

        if ($this->application_deadline === null) {
            return true;
        }

        return $this->application_deadline->gte(now()->startOfDay());
    }

    public static function vacancyAcceptsApplications(JobVacancy $vacancy): bool
    {
        if (! $vacancy->is_published || $vacancy->is_closed) {
            return false;
        }

        if ($vacancy->closing_date === null) {
            return true;
        }

        return $vacancy->closing_date->gte(now()->startOfDay());
    }

    public function scopeVisibleOnPublic($query)
    {
        return $query
            ->where('is_published', true)
            ->where(function ($q) {
                $q->where(function ($external) {
                    $external->where('opportunity_scope', self::SCOPE_EXTERNAL)
                        ->where(function ($deadline) {
                            $deadline->whereNull('application_deadline')
                                ->orWhere('application_deadline', '>=', now()->toDateString());
                        });
                })->orWhere(function ($internal) {
                    $internal->where('opportunity_scope', self::SCOPE_INTERNAL)
                        ->whereNotNull('job_vacancy_id')
                        ->whereHas('jobVacancy', fn ($vq) => self::applyOpenVacancyQuery($vq));
                });
            });
    }

    public static function applyOpenVacancyQuery($query): void
    {
        $query->where('is_published', 1)
            ->where(function ($q) {
                $q->where('is_closed', 0)
                    ->orWhere('closing_date', '>=', now()->toDateString());
            });
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