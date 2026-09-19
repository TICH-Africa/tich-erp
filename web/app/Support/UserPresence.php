<?php

namespace App\Support;

use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class UserPresence
{
    /** Seconds of inactivity before a user is considered offline. */
    public const ONLINE_WINDOW_SECONDS = 300;

    /**
     * @param  Collection<int, User>|array<int, int|string>  $usersOrIds
     * @return array<int, array{online: bool, last_seen_at: ?\Carbon\Carbon, label: string}>
     */
    public static function forUsers(Collection|array $usersOrIds): array
    {
        $ids = collect($usersOrIds)
            ->map(function ($item) {
                if ($item instanceof User) {
                    return (int) $item->id;
                }

                return (int) $item;
            })
            ->filter()
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return [];
        }

        $activityByUser = [];
        if (Schema::hasTable('sessions')) {
            $activityByUser = DB::table('sessions')
                ->whereIn('user_id', $ids->all())
                ->whereNotNull('user_id')
                ->groupBy('user_id')
                ->selectRaw('user_id, MAX(last_activity) as last_activity')
                ->pluck('last_activity', 'user_id')
                ->all();
        }

        $usersById = $usersOrIds instanceof Collection && $usersOrIds->first() instanceof User
            ? $usersOrIds->keyBy('id')
            : User::query()->whereIn('id', $ids->all())->get(['id', 'last_login_at'])->keyBy('id');

        $threshold = now()->subSeconds(self::ONLINE_WINDOW_SECONDS)->getTimestamp();
        $presence = [];

        foreach ($ids as $id) {
            $sessionActivity = isset($activityByUser[$id]) ? (int) $activityByUser[$id] : null;
            $user = $usersById->get($id);
            $lastLogin = $user?->last_login_at;

            $lastSeenAt = null;
            if ($sessionActivity) {
                $lastSeenAt = Carbon::createFromTimestamp($sessionActivity);
            } elseif ($lastLogin instanceof CarbonInterface) {
                $lastSeenAt = Carbon::instance($lastLogin);
            }

            $online = $sessionActivity !== null && $sessionActivity >= $threshold;

            $presence[$id] = [
                'online' => $online,
                'last_seen_at' => $lastSeenAt,
                'label' => $online
                    ? 'Online'
                    : self::lastSeenLabel($lastSeenAt),
            ];
        }

        return $presence;
    }

    public static function lastSeenLabel(?CarbonInterface $at): string
    {
        if (! $at) {
            return 'Last seen unavailable';
        }

        if ($at->isToday()) {
            return 'Last seen at '.$at->format('g:i A');
        }

        if ($at->isCurrentYear()) {
            return 'Last seen at '.$at->format('j M, g:i A');
        }

        return 'Last seen at '.$at->format('j M Y, g:i A');
    }
}
