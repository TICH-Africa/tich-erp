<?php

namespace App\Services\Procurement;

use App\Models\Asset;
use App\Models\AssetMovement;
use Illuminate\Support\Facades\DB;

class AssetMovementService
{
    public function transferAsset(Asset $asset, array $data): AssetMovement
    {
        return DB::transaction(function () use ($asset, $data) {
            $fromLocation = $data['from_location'] ?? $asset->location_name;
            $toLocation = $data['to_location'];

            $movement = AssetMovement::query()->create([
                'asset_id' => $asset->id,
                'from_location' => $fromLocation,
                'to_location' => $toLocation,
                'reason' => $data['reason'],
                'movement_type' => $data['movement_type'] ?? 'transfer',
                'requested_by' => auth()->id(),
                'approved_by' => auth()->id(),
                'approval_status' => 'approved',
                'status' => 'completed',
                'movement_date' => $data['movement_date'] ?? now()->format('Y-m-d'),
                'approved_at' => now(),
                'notes' => $data['notes'] ?? null,
            ]);

            $asset->update([
                'location_name' => $toLocation,
                'building' => $data['building'] ?? $asset->building,
                'room' => $data['room'] ?? $asset->room,
                'custodian_id' => $data['custodian_id'] ?? $asset->custodian_id,
            ]);

            return $movement;
        });
    }
}
