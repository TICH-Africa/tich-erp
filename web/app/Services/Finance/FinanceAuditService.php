<?php

namespace App\Services\Finance;

use App\Services\AuditService;

class FinanceAuditService
{
    public function __construct(
        protected AuditService $audit,
    ) {}

    public function log(
        string $action,
        string $entityType,
        string|int|null $entityId = null,
        ?array $oldValue = null,
        ?array $newValue = null,
        ?string $reason = null,
    ): void {
        $this->audit->log($action, $entityType, $entityId, $oldValue, $newValue, $reason);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder<\App\Models\AuditLog>
     */
    public function query(array $filters = [])
    {
        return $this->audit->query(array_merge(['module' => 'finance'], $filters));
    }
}
