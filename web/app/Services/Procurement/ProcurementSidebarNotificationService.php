<?php

namespace App\Services\Procurement;

use App\Events\ProcurementSidebarCountsUpdated;
use App\Models\AssetAudit;
use App\Models\AssetDisposal;
use App\Models\AssetMaintenance;
use App\Models\AssetMovement;
use App\Models\GoodsReceivedNote;
use App\Models\ProcurementRequisition;
use App\Models\Rfq;
use App\Models\StockAlert;
use App\Models\StockIssue;
use App\Models\Supplier;
use App\Support\SafelyBroadcasts;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class ProcurementSidebarNotificationService
{
    use SafelyBroadcasts;

    public const CACHE_KEY = 'procurement.sidebar.counts';

    public const CACHE_TTL_SECONDS = 30;

    /** @var array<string, string> */
    public const MENU_KEYS = [
        'requisitions.pending' => 'Requisitions',
        'suppliers.pending' => 'Suppliers',
        'rfqs.actionable' => 'RFQs',
        'grns.pending' => 'GRNs',
        'stock-alerts.active' => 'Stock alerts',
        'asset-movements.pending' => 'Asset movements',
        'asset-maintenance.open' => 'Maintenance',
        'asset-disposals.pending' => 'Disposals',
        'asset-audits.pending' => 'Audits',
        'stock-issues.pending' => 'Stock issues',
    ];

    public function counts(bool $fresh = false): array
    {
        if ($fresh) {
            return $this->computeCounts();
        }

        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL_SECONDS, fn () => $this->computeCounts());
    }

    public function formattedCounts(bool $fresh = false): array
    {
        return collect($this->counts($fresh))
            ->mapWithKeys(fn (int $count, string $key) => [$key => $this->formatCount($count)])
            ->all();
    }

    public function formatCount(int $count): ?string
    {
        if ($count <= 0) {
            return null;
        }

        return $count > 99 ? '99+' : (string) $count;
    }

    public function broadcastCounts(): void
    {
        Cache::forget(self::CACHE_KEY);

        $counts = $this->counts(true);
        $labels = collect($counts)
            ->mapWithKeys(fn (int $count, string $key) => [$key => $this->formatCount($count)])
            ->all();

        $this->safelyBroadcast(fn () => broadcast(new ProcurementSidebarCountsUpdated($counts, $labels)));
    }

    /**
     * @return array<string, int>
     */
    private function computeCounts(): array
    {
        return [
            'requisitions.pending' => $this->pendingRequisitionsCount(),
            'suppliers.pending' => $this->pendingSuppliersCount(),
            'rfqs.actionable' => $this->actionableRfqsCount(),
            'grns.pending' => $this->pendingGrnsCount(),
            'stock-alerts.active' => $this->activeStockAlertsCount(),
            'asset-movements.pending' => $this->pendingAssetMovementsCount(),
            'asset-maintenance.open' => $this->openAssetMaintenanceCount(),
            'asset-disposals.pending' => $this->pendingAssetDisposalsCount(),
            'asset-audits.pending' => $this->pendingAssetAuditsCount(),
            'stock-issues.pending' => $this->pendingStockIssuesCount(),
        ];
    }

    private function pendingRequisitionsCount(): int
    {
        if (! Schema::hasTable('procurement_requisitions')) {
            return 0;
        }

        return ProcurementRequisition::query()
            ->whereIn('status', ['submitted', 'hod_approved', 'finance_approved', 'ceo_approved'])
            ->count();
    }

    private function pendingSuppliersCount(): int
    {
        if (! Schema::hasTable('suppliers')) {
            return 0;
        }

        return Supplier::query()
            ->whereIn('compliance_status', ['pending', 'under_review'])
            ->count();
    }

    private function actionableRfqsCount(): int
    {
        if (! Schema::hasTable('rfqs')) {
            return 0;
        }

        return Rfq::query()
            ->where(function ($query) {
                $query->where('status', 'closed')
                    ->orWhere(function ($inner) {
                        $inner->where('status', 'awarded')
                            ->where('approval_status', 'pending');
                    });
            })
            ->count();
    }

    private function pendingGrnsCount(): int
    {
        if (! Schema::hasTable('goods_received_notes')) {
            return 0;
        }

        return GoodsReceivedNote::query()
            ->where('inspection_status', 'pending')
            ->count();
    }

    private function activeStockAlertsCount(): int
    {
        if (! Schema::hasTable('stock_alerts')) {
            return 0;
        }

        return StockAlert::query()
            ->where('status', 'active')
            ->count();
    }

    private function pendingAssetMovementsCount(): int
    {
        if (! Schema::hasTable('asset_movements')) {
            return 0;
        }

        return AssetMovement::query()
            ->where('approval_status', 'pending')
            ->count();
    }

    private function openAssetMaintenanceCount(): int
    {
        if (! Schema::hasTable('asset_maintenance')) {
            return 0;
        }

        return AssetMaintenance::query()
            ->whereIn('status', ['scheduled', 'in_progress'])
            ->count();
    }

    private function pendingAssetDisposalsCount(): int
    {
        if (! Schema::hasTable('asset_disposals')) {
            return 0;
        }

        return AssetDisposal::query()
            ->where('approval_status', 'pending')
            ->count();
    }

    private function pendingAssetAuditsCount(): int
    {
        if (! Schema::hasTable('asset_audits')) {
            return 0;
        }

        return AssetAudit::query()
            ->whereIn('status', ['submitted', 'pending'])
            ->count();
    }

    private function pendingStockIssuesCount(): int
    {
        if (! Schema::hasTable('stock_issues')) {
            return 0;
        }

        return StockIssue::query()
            ->where(function ($query) {
                $query->where('approval_status', 'pending')
                    ->orWhere('status', 'pending');
            })
            ->count();
    }
}
