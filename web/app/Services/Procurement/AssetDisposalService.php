<?php

namespace App\Services\Procurement;

use App\Models\AssetDisposal;

class AssetDisposalService
{
    public function requestDisposal(array $data): AssetDisposal
    {
        $data['requested_by'] = auth()->id();
        $data['approval_status'] = 'pending';
        $data['status'] = 'pending';

        return AssetDisposal::query()->create($data);
    }

    public function approveDisposal(AssetDisposal $disposal): AssetDisposal
    {
        return $disposal->update([
            'approval_status' => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'status' => 'completed',
            'disposal_date' => $disposal->disposal_date ?? now()->format('Y-m-d'),
        ]);
    }
}
