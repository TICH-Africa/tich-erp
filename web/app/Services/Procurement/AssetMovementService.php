<?php

namespace App\Services\Procurement;

use App\Models\AssetMovement;

class AssetMovementService
{
    public function submitMovement(array $data): AssetMovement
    {
        $data['requested_by'] = auth()->id();
        $data['approval_status'] = 'pending';
        $data['status'] = 'pending';

        return AssetMovement::query()->create($data);
    }

    public function approveMovement(AssetMovement $movement): AssetMovement
    {
        return $movement->update([
            'approval_status' => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'status' => 'completed',
            'movement_date' => $movement->movement_date ?? now()->format('Y-m-d'),
        ]);
    }
}
