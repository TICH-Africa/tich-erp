<?php

namespace App\Models\Portal;

use Illuminate\Database\Eloquent\Model;

class CmsPage extends Model
{
    protected $table = 'cms_pages';

    public $timestamps = false;

    protected $fillable = [
        'slug',
        'title',
        'body',
        'status',
        'published_at',
        'seo_meta_title',
        'seo_meta_description',
        'created_by',
        'updated_by',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public const SLUG_PRIVACY = 'privacy';

    public const SLUG_TERMS = 'terms';

    /** @return list<string> */
    public static function managedSlugs(): array
    {
        return [self::SLUG_PRIVACY, self::SLUG_TERMS];
    }

    public function isPublished(): bool
    {
        return $this->status === 'published'
            && $this->published_at
            && $this->published_at->lte(now());
    }

    public static function publishedBySlug(string $slug): ?self
    {
        $page = static::query()->where('slug', $slug)->first();

        return ($page && $page->isPublished()) ? $page : null;
    }
}
