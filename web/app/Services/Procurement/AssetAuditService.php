<?php

namespace App\Services\Procurement;

use App\Models\AssetAudit;

class AssetAuditService
{
    public function submitVerification(array $data): AssetAudit
    {
        // Keep auditor_id from the form (staff id). Do not overwrite with users.id.
        $data['submitted_at'] = now();
        $data['status'] = 'submitted';

        return AssetAudit::query()->create($data);
    }

    public function reviewVerification(AssetAudit $audit, array $data): AssetAudit
    {
        $audit->update([
            'status' => 'reviewed',
            'reviewed_by' => auth()->user()?->staff_id,
            'reviewed_at' => now(),
            'review_notes' => $data['review_notes'] ?? null,
            'verification_status' => $data['verification_status'] ?? $audit->verification_status,
            'condition' => $data['condition'] ?? $audit->condition,
        ]);

        return $audit->fresh(['asset', 'auditor', 'reviewer']);
    }
}
