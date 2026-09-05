<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

class InAppNotification extends Model
{
    protected $table = 'notifications';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'template_id',
        'title',
        'body',
        'channels_sent',
        'related_entity_type',
        'related_entity_id',
        'action_url',
        'is_read',
        'read_at',
        'read_device_info',
        'priority',
        'is_dismissed',
        'created_at',
    ];

    protected $casts = [
        'channels_sent' => 'array',
        'is_read' => 'boolean',
        'is_dismissed' => 'boolean',
        'read_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isUnread(): bool
    {
        return ! $this->is_read;
    }

    public function actionUrl(?User $viewer = null): ?string
    {
        return app(\App\Services\NotificationActionUrlResolver::class)->resolve($this, $viewer);
    }

    public function markRead(?string $deviceInfo = null): void
    {
        if ($this->is_read) {
            return;
        }

        $this->forceFill([
            'is_read' => 1,
            'read_at' => now(),
            'read_device_info' => $deviceInfo,
        ])->save();
    }

    public static function unreadCountForUser(int $userId): int
    {
        return static::query()
            ->where('user_id', $userId)
            ->where('is_read', 0)
            ->where('is_dismissed', 0)
            ->count();
    }

    /**
     * @return Collection<int, self>
     */
    public static function forUser(int $userId, int $limit = 100): Collection
    {
        return static::query()
            ->where('user_id', $userId)
            ->where('is_dismissed', 0)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }
}
