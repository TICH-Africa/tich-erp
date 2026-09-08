<?php

namespace App\Services\Sidebar;

use App\Events\IctSidebarCountsUpdated;
use App\Models\ErpRegistrationInvitation;
use App\Models\Portal\CmsPage;
use App\Services\Sidebar\Concerns\FormatsSidebarBadgeCounts;
use App\Support\SafelyBroadcasts;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class IctSidebarNotificationService
{
    use FormatsSidebarBadgeCounts;
    use SafelyBroadcasts;

    public const CACHE_KEY = 'ict.sidebar.counts';

    public const CACHE_TTL_SECONDS = 30;

    /** @var array<string, string> */
    public const MENU_KEYS = [
        'invites' => 'ERP registration invites',
        'pages' => 'Legal pages',
    ];

    /** @var list<string> */
    public const DASHBOARD_LEAF_KEYS = ['invites', 'pages'];

    /**
     * @return array<string, int>
     */
    public function counts(bool $fresh = false): array
    {
        if ($fresh) {
            return $this->computeCounts();
        }

        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL_SECONDS, fn () => $this->computeCounts());
    }

    /**
     * @return array<string, string|null>
     */
    public function labels(bool $fresh = false): array
    {
        return $this->formattedCounts($this->counts($fresh));
    }

    public function broadcastCounts(): void
    {
        Cache::forget(self::CACHE_KEY);
        $counts = $this->counts(true);

        $this->safelyBroadcast(fn () => broadcast(new IctSidebarCountsUpdated(
            $counts,
            $this->formattedCounts($counts)
        )));
    }

    public function dashboardTotal(): int
    {
        $counts = $this->counts();

        return (int) collect(self::DASHBOARD_LEAF_KEYS)->sum(fn (string $key) => (int) ($counts[$key] ?? 0));
    }

    /**
     * @return array<string, int>
     */
    private function computeCounts(): array
    {
        $invites = 0;
        if (Schema::hasTable('erp_registration_invitations')) {
            $invites = ErpRegistrationInvitation::query()
                ->whereNull('used_at')
                ->where('expires_at', '>', now())
                ->count();
        }

        $pages = 0;
        if (Schema::hasTable('cms_pages')) {
            $pages = CmsPage::query()->where('status', 'draft')->count();
        }

        return [
            'invites' => $invites,
            'pages' => $pages,
        ];
    }
}
