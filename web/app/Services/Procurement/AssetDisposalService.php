<?php

namespace App\Services\Procurement;

use App\Models\AssetDisposal;
use RuntimeException;

class AssetDisposalService
{
    public function requestDisposal(array $data): AssetDisposal
    {
        $staffId = auth()->user()?->staff_id;
        if (! $staffId) {
            throw new RuntimeException('Your account is not linked to a staff record.');
        }

        $data['requested_by'] = $staffId;
        $data['approval_status'] = 'pending';
        $data['status'] = 'pending';

        return AssetDisposal::query()->create($data);
    }

    public function approveDisposal(AssetDisposal $disposal): AssetDisposal
    {
        $staffId = auth()->user()?->staff_id;

        $disposal->update([
            'approval_status' => 'approved',
            'approved_by' => $staffId,
            'approved_at' => now(),
            'status' => 'completed',
            'disposal_date' => $disposal->disposal_date ?? now()->format('Y-m-d'),
        ]);

        return $disposal->fresh(['asset', 'requestedBy', 'approvedBy']);
    }
}
