<?php

namespace App\Services\Procurement;

use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use App\Models\StockAlert;
use Illuminate\Support\Facades\Auth;

class InventoryService
{
    public function updateStock(int $itemId, int $quantity, string $type, int $recordedBy, array $reference = [], array $metadata = []): InventoryItem
    {
        $item = InventoryItem::query()->findOrFail($itemId);

        $adjustment = match ($type) {
            'receipt' => $quantity,
            'issue' => -$quantity,
            'adjustment' => $quantity,
            default => 0,
        };

        $item->current_stock = max(0, $item->current_stock + $adjustment);
        $item->save();

        $unitCost = $metadata['unit_cost'] ?? $item->unit_cost;

        InventoryTransaction::query()->create([
            'inventory_item_id' => $itemId,
            'transaction_type' => $type,
            'quantity' => abs($adjustment),
            'unit_cost' => $unitCost,
            'total_cost' => round(abs($adjustment) * $unitCost, 2),
            'reference_table' => $reference['table'] ?? null,
            'reference_id' => $reference['id'] ?? null,
            'from_location' => $reference['from_location'] ?? null,
            'to_location' => $reference['to_location'] ?? null,
            'department_id' => $reference['department_id'] ?? null,
            'recorded_by' => $recordedBy,
            'transaction_date' => now()->format('Y-m-d'),
            'notes' => $metadata['notes'] ?? null,
        ]);

        if ($item->isLowStock()) {
            $this->triggerLowStockAlert($item);
        } else {
            StockAlert::query()
                ->where('inventory_item_id', $itemId)
                ->where('status', 'active')
                ->update(['status' => 'closed', 'closed_at' => now()]);
        }

        return $item->fresh();
    }

    public function triggerLowStockAlert(InventoryItem $item): StockAlert
    {
        return StockAlert::query()->create([
            'inventory_item_id' => $item->id,
            'alert_type' => 'low_stock',
            'current_stock' => $item->current_stock,
            'reorder_level' => $item->reorder_level,
            'recommended_quantity' => $item->recommendedOrderQuantity(),
            'channels' => ['in_app', 'email'],
            'sent_to' => [],
            'status' => 'active',
            'notes' => "Stock for {$item->item_name} ({$item->item_code}) is below reorder point.",
        ]);
    }
}
