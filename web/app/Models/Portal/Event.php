<?php

namespace App\Models\Portal;

use App\Models\Concerns\PrunesStoredFiles;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class Event extends Model
{
    use PrunesStoredFiles;

    protected $table = 'events';

    /** @var array<string, string> */
    protected array $storedFiles = [
        'cover_image_path' => 'public',
    ];

    public $timestamps = false;

    protected $fillable = [
        'title', 'subtitle', 'slug', 'event_type', 'description', 'cover_image_path',
        'start_datetime', 'end_datetime', 'venue', 'registration_url_or_form',
        'is_public', 'is_featured', 'is_important',
    ];

    protected $casts = [
        'start_datetime' => 'datetime',
        'end_datetime' => 'datetime',
        'is_public' => 'boolean',
        'is_featured' => 'boolean',
        'is_important' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function resolveRouteBinding($value, $field = null)
    {
        $field = $field ?: $this->getRouteKeyName();

        $query = static::query();

        if (ctype_digit((string) $value)) {
            return $query->where('id', $value)->orWhere($field, $value)->firstOrFail();
        }

        return $query->where($field, $value)->firstOrFail();
    }

    /**
     * The one event currently holding the hero countdown: public, flagged Important and not yet
     * started. Guarded on the column so the event screens still save before the production ALTER
     * is pasted. A private event never reaches the website, so it never takes the slot.
     */
    public static function countdownHolder(?int $exceptId = null): ?self
    {
        if (! Schema::hasColumn('events', 'is_important')) {
            return null;
        }

        return static::query()
            ->where('is_public', true)
            ->where('is_important', true)
            ->where('start_datetime', '>', now())
            ->when($exceptId, fn ($query) => $query->where('id', '!=', $exceptId))
            ->orderBy('start_datetime')
            ->first();
    }

    /**
     * One event carries the countdown at a time, so the flag only frees up once that day has passed.
     */
    public static function guardCountdownIsFree(bool $important, ?int $exceptId = null): void
    {
        if (! $important) {
            return;
        }

        $held = static::countdownHolder($exceptId);

        if ($held) {
            throw ValidationException::withMessages([
                'is_important' => 'Only one event can show the countdown at a time. "'
                    .$held->title.'" ('.$held->start_datetime->format('M j, Y').') is still counting down. '
                    .'Untick Important on it first, or wait until that day has passed.',
            ]);
        }
    }

    public static function uniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title) ?: 'event';
        $slug = $base;
        $i = 2;

        while (static::query()
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->where('slug', $slug)
            ->exists()) {
            $slug = $base.'-'.$i;
            $i++;
        }

        return Str::limit($slug, 300, '');
    }

    public function coverImageUrl(): ?string
    {
        if (! $this->cover_image_path) {
            return null;
        }

        return \App\Support\PublicAsset::media($this->cover_image_path);
    }
}
