<?php

namespace App\Services\Procurement;

use App\Models\AssetMaintenance;

class AssetMaintenanceService
{
    public function scheduleMaintenance(array $data): AssetMaintenance
    {
        $data['status'] = 'scheduled';

        return AssetMaintenance::query()->create($data);
    }

    public function completeMaintenance(AssetMaintenance $maintenance, array $data): AssetMaintenance
    {
        return $maintenance->update([
            'status' => 'completed',
            'completed_by' => auth()->id(),
            'completed_date' => now()->format('Y-m-d'),
            'work_done' => $data['work_done'] ?? $maintenance->work_done,
            'parts_used' => $data['parts_used'] ?? $maintenance->parts_used,
            'parts_cost' => $data['parts_cost'] ?? $maintenance->parts_cost,
            'labour_cost' => $data['labour_cost'] ?? $maintenance->labour_cost,
            'technician_name' => $data['technician_name'] ?? $maintenance->technician_name,
            'technician_phone' => $data['technician_phone'] ?? $maintenance->technician_phone,
        ]);
    }
}
