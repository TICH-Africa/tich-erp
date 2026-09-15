<?php

namespace App\Services\Procurement;

use App\Models\Asset;
use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use App\Models\GoodsReceivedNote;
use App\Models\GrnItem;
use App\Models\ProcurementRequisition;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class GrnService
{
    public function createGrn(array $data): GoodsReceivedNote
    {
        $items = $data['items'] ?? [];
        unset($data['items']);

        $data['received_date'] = $data['received_date'] ?? now()->format('Y-m-d');
        $data['received_by'] = Auth::id();
        $data['inspection_status'] = 'pending';
        $data['is_complete'] = false;

        return DB::transaction(function () use ($data, $items) {
            $grn = GoodsReceivedNote::query()->create($data);

            foreach ($items as $item) {
                $item['grn_id'] = $grn->id;
                $item['total_cost'] = round((float) ($item['quantity_received'] ?? 0) * (float) ($item['unit_cost'] ?? 0), 2);
                GrnItem::query()->create($item);
            }

            return $grn->load('items');
        });
    }

    public function completeInspection(GoodsReceivedNote $grn, string $notes = null): GoodsReceivedNote
    {
        return DB::transaction(function () use ($grn, $notes) {
            $grn->update([
                'inspection_status' => 'complete',
                'is_complete' => true,
                'inspection_notes' => $notes,
            ]);

            foreach ($grn->items as $item) {
                if ($item->classification === 'asset') {
                    Asset::query()->create([
                        'asset_name' => $item->item_name,
                        'asset_category' => $item->category ?? 'equipment',
                        'serial_number' => null,
                        'description' => $item->item_description,
                        'acquisition_date' => $grn->received_date,
                        'acquisition_cost' => $item->total_cost,
                        'supplier_id' => $item->supplier_id,
                        'purchase_order_id' => $item->purchase_order_id,
                        'useful_life_years' => 5,
                        'depreciation_method' => 'straight_line',
                        'condition' => $item->condition === 'damaged' ? 'damaged' : 'new',
                        'tag_number' => null,
                        'qr_code_path' => null,
                        'custodian_id' => null,
                        'location_name' => null,
                        'grn_id' => $grn->id,
                        'procurement_requisition_id' => $grn->purchaseOrder?->requisition_id,
                    ]);
                } else {
                    $inventoryItem = InventoryItem::query()
                        ->where('item_name', $item->item_name)
                        ->where('supplier_id', $item->supplier_id)
                        ->first();

                    if ($inventoryItem) {
                        $inventoryItem->current_stock = $inventoryItem->current_stock + (int) $item->quantity_received;
                        $inventoryItem->save();

                        InventoryTransaction::query()->create([
                            'inventory_item_id' => $inventoryItem->id,
                            'transaction_type' => 'receipt',
                            'quantity' => (int) $item->quantity_received,
                            'unit_cost' => $item->unit_cost,
                            'total_cost' => $item->total_cost,
                            'reference_table' => 'goods_received_notes',
                            'reference_id' => $grn->id,
                            'recorded_by' => $grn->received_by,
                            'transaction_date' => $grn->received_date,
                            'notes' => "GRN {$grn->grn_number}: {$item->item_name}",
                        ]);
                    }
                }

                $item->update(['status' => $item->condition === 'damaged' ? 'quarantined' : 'registered']);
            }

            return $grn->fresh();
        });
    }
}
