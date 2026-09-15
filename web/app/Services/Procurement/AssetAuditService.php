<?php

namespace App\Services\Procurement;

use App\Models\AssetAudit;

class AssetAuditService
{
    public function submitVerification(array $data): AssetAudit
    {
        $data['auditor_id'] = auth()->id();
        $data['submitted_at'] = now();
        $data['status'] = 'submitted';

        return AssetAudit::query()->create($data);
    }

    public function reviewVerification(AssetAudit $audit, array $data): AssetAudit
    {
        return $audit->update([
            'status' => 'reviewed',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
            'review_notes' => $data['review_notes'] ?? null,
            'verification_status' => $data['verification_status'] ?? $audit->verification_status,
            'condition' => $data['condition'] ?? $audit->condition,
        ]);
    }
}
