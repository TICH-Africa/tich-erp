<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Support\ClientContextResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class AuditService
{
    public function __construct(protected ClientContextResolver $clientContextResolver) {}

    public function log(
        string $action,
        string $entityType,
        string|int|null $entityId = null,
        ?array $oldValue = null,
        ?array $newValue = null,
        ?string $reason = null,
        string $status = 'success',
        ?int $userId = null,
        ?Request $request = null,
    ): ?AuditLog {
        if (! $this->isAvailable()) {
            return null;
        }

        $userId = $userId ?? Auth::id();
        $module = $this->resolveModule($action);
        $createdAt = now();

        $sanitizedOld = $this->sanitize($oldValue);
        $sanitizedNew = $this->sanitize($newValue);

        $previousHash = $this->supportsHashChain() ? $this->latestRecordHash() : null;
        $clientContext = $this->resolveClientContext($request);
        $ipAddress = $clientContext['ip_address'] ?? $request?->ip();
        $userAgent = $clientContext['user_agent'] ?? ($request?->userAgent() ? substr($request->userAgent(), 0, 500) : null);

        $recordHash = null;

        if ($this->supportsHashChain()) {
            $hashPayload = [
                'user_id' => $userId,
                'action' => $action,
                'module' => $module,
                'entity_type' => $entityType,
                'entity_id' => (string) ($entityId ?? ''),
                'old_value' => $sanitizedOld,
                'new_value' => $sanitizedNew,
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
                'reason' => $reason,
                'status' => $status,
                'created_at' => $createdAt->toIso8601String(),
                'previous_hash' => $previousHash,
            ];

            if ($this->hasClientContextColumn() && $clientContext !== []) {
                $hashPayload['client_context'] = $clientContext;
            }

            $recordHash = $this->computeHash($hashPayload);
        }

        $payload = [
            'user_id' => $userId,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => (string) ($entityId ?? ''),
            'old_value' => $sanitizedOld,
            'new_value' => $sanitizedNew,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'reason' => $reason,
            'created_at' => $createdAt,
        ];

        if ($this->hasClientContextColumn() && $clientContext !== []) {
            $payload['client_context'] = $clientContext;
        }

        if (Schema::hasColumn('audit_logs', 'status')) {
            $payload['status'] = $status;
        }

        if (Schema::hasColumn('audit_logs', 'module')) {
            $payload['module'] = $module;
        }

        if ($this->supportsHashChain()) {
            $payload['previous_hash'] = $previousHash;
            $payload['record_hash'] = $recordHash;
        }

        return AuditLog::create($payload);
    }

    public function verifyChain(?int $limit = null): array
    {
        if (! $this->isAvailable()) {
            return [
                'verified' => false,
                'message' => 'Audit log table is not available',
                'checked' => 0,
                'broken_at_id' => null,
            ];
        }

        if (! $this->supportsHashChain()) {
            return [
                'verified' => false,
                'message' => 'Hash chain columns are not available - run migrations',
                'checked' => 0,
                'broken_at_id' => null,
            ];
        }

        $query = AuditLog::query()->orderBy('id');

        if ($limit) {
            $query->limit($limit);
        }

        $logs = $query->get();
        $expectedPrevious = config('audit.genesis_hash');
        $checked = 0;

        foreach ($logs as $log) {
            if ($log->previous_hash !== $expectedPrevious) {
                return [
                    'verified' => false,
                    'message' => 'Previous hash mismatch',
                    'checked' => $checked,
                    'broken_at_id' => $log->id,
                ];
            }

            $hashPayload = [
                'user_id' => $log->user_id,
                'action' => $log->action,
                'module' => $log->module,
                'entity_type' => $log->entity_type,
                'entity_id' => $log->entity_id,
                'old_value' => $log->old_value,
                'new_value' => $log->new_value,
                'ip_address' => $log->ip_address,
                'user_agent' => $log->user_agent,
                'reason' => $log->reason,
                'status' => $log->status,
                'created_at' => $log->created_at?->toIso8601String(),
                'previous_hash' => $log->previous_hash,
            ];

            if ($this->hasClientContextColumn() && ! empty($log->client_context)) {
                $hashPayload['client_context'] = $log->client_context;
            }

            $recomputed = $this->computeHash($hashPayload);

            if ($recomputed !== $log->record_hash) {
                return [
                    'verified' => false,
                    'message' => 'Record hash mismatch - possible tampering',
                    'checked' => $checked,
                    'broken_at_id' => $log->id,
                ];
            }

            $expectedPrevious = $log->record_hash;
            $checked++;
        }

        return [
            'verified' => true,
            'message' => 'Audit chain verified successfully',
            'checked' => $checked,
            'broken_at_id' => null,
        ];
    }

    public function query(array $filters = [])
    {
        $query = AuditLog::query()
            ->with([
                'user:id,email,user_type,staff_id,student_id',
                'user.staff:id,first_name,surname,employee_number',
                'user.student:id,registration_number,application_id',
                'user.student.applicant:id,first_name,surname',
            ])
            ->orderByDesc('created_at');

        if (! empty($filters['action'])) {
            $query->where('action', $filters['action']);
        }

        if (! empty($filters['module']) && Schema::hasColumn('audit_logs', 'module')) {
            $module = $filters['module'];
            // Treat "hr" as covering leave/employee prefixes that map to HR.
            if ($module === 'hr') {
                $query->where(function ($q) {
                    $q->where('module', 'hr')
                        ->orWhere('action', 'like', 'hr.%')
                        ->orWhere('action', 'like', 'leave.%')
                        ->orWhere('action', 'like', 'employee.%')
                        ->orWhere(function ($inner) {
                            $inner->where('module', 'staff')
                                ->where(function ($staffQ) {
                                    $staffQ->where('action', 'like', 'staff.created%')
                                        ->orWhere('action', 'like', 'staff.updated%')
                                        ->orWhere('action', 'like', 'staff.deleted%')
                                        ->orWhere('action', 'like', 'staff.onboarding.%')
                                        ->orWhere('action', 'like', 'staff.profile.%')
                                        ->orWhere('action', 'like', 'staff.status.%')
                                        ->orWhere('action', 'like', 'staff.document.%')
                                        ->orWhere('action', 'like', 'staff.allowance.%');
                                });
                        });
                });
            } elseif ($module === 'admin' || $module === 'core') {
                $query->where(function ($q) {
                    $q->whereIn('module', ['admin', 'core'])
                        ->orWhere('action', 'like', 'core.%');
                });
            } else {
                $query->where('module', $module);
            }
        }

        if (! empty($filters['entity_type'])) {
            $query->where('entity_type', $filters['entity_type']);
        }

        if (! empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (! empty($filters['account_type'])) {
            match ($filters['account_type']) {
                'staff' => $query->whereHas('user', fn ($q) => $q->whereNotNull('staff_id')),
                'student' => $query->whereHas('user', fn ($q) => $q->whereNotNull('student_id')),
                'system' => $query->whereNull('user_id'),
                default => null,
            };
        }

        if (! empty($filters['account'])) {
            $account = trim((string) $filters['account']);
            $query->where(function ($q) use ($account) {
                $q->whereHas('user', function ($userQ) use ($account) {
                    $userQ->where('email', 'like', "%{$account}%")
                        ->orWhereHas('staff', function ($staffQ) use ($account) {
                            $staffQ->where('employee_number', 'like', "%{$account}%")
                                ->orWhere('first_name', 'like', "%{$account}%")
                                ->orWhere('surname', 'like', "%{$account}%");
                        })
                        ->orWhereHas('student', function ($studentQ) use ($account) {
                            $studentQ->where('registration_number', 'like', "%{$account}%")
                                ->orWhereHas('applicant', function ($appQ) use ($account) {
                                    $appQ->where('first_name', 'like', "%{$account}%")
                                        ->orWhere('surname', 'like', "%{$account}%")
                                        ->orWhere('email', 'like', "%{$account}%");
                                });
                        });
                });
            });
        }

        if (! empty($filters['status']) && Schema::hasColumn('audit_logs', 'status')) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['from'])) {
            $query->where('created_at', '>=', $filters['from'].(strlen($filters['from']) <= 10 ? ' 00:00:00' : ''));
        }

        if (! empty($filters['to'])) {
            $query->where('created_at', '<=', $filters['to'].(strlen($filters['to']) <= 10 ? ' 23:59:59' : ''));
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('action', 'like', "%{$search}%")
                    ->orWhere('entity_type', 'like', "%{$search}%")
                    ->orWhere('entity_id', 'like', "%{$search}%")
                    ->orWhere('reason', 'like', "%{$search}%")
                    ->orWhere('ip_address', 'like', "%{$search}%")
                    ->orWhere('module', 'like', "%{$search}%");

                if ($this->hasClientContextColumn()) {
                    $q->orWhere('client_context->browser', 'like', "%{$search}%")
                        ->orWhere('client_context->os', 'like', "%{$search}%")
                        ->orWhere('client_context->device_type', 'like', "%{$search}%")
                        ->orWhere('client_context->location->label', 'like', "%{$search}%");
                }
            });
        }

        return $query;
    }

    public function moduleOptions(): array
    {
        $configured = config('audit.modules', []);
        $fromDb = [];

        if ($this->isAvailable() && Schema::hasColumn('audit_logs', 'module')) {
            $fromDb = AuditLog::query()
                ->whereNotNull('module')
                ->distinct()
                ->orderBy('module')
                ->pluck('module')
                ->all();
        }

        $options = [];
        foreach (array_unique(array_merge(array_keys($configured), $fromDb)) as $key) {
            $options[$key] = $configured[$key] ?? ucfirst(str_replace('_', ' ', (string) $key));
        }

        asort($options);

        return $options;
    }

    public function resolveModule(string $action): ?string
    {
        $configured = config("audit.actions.{$action}.module");
        if ($configured) {
            return $configured;
        }

        $prefix = explode('.', $action)[0] ?? null;
        if (! $prefix) {
            return null;
        }

        $modules = config('audit.modules', []);
        if (isset($modules[$prefix])) {
            // Prefer the canonical module key (e.g. leave -> hr)
            return match ($prefix) {
                'leave', 'employee' => 'hr',
                'access' => 'security',
                'core' => 'admin',
                default => $prefix,
            };
        }

        return $prefix;
    }

    public function sanitize(?array $data): ?array
    {
        if ($data === null) {
            return null;
        }

        $sensitiveKeys = config('audit.sensitive_keys', []);

        $walk = function (array $items) use (&$walk, $sensitiveKeys): array {
            $result = [];

            foreach ($items as $key => $value) {
                if (in_array(strtolower((string) $key), $sensitiveKeys, true)) {
                    $result[$key] = '[REDACTED]';

                    continue;
                }

                if (is_array($value)) {
                    $result[$key] = $walk($value);
                } else {
                    $result[$key] = $value;
                }
            }

            return $result;
        };

        return $walk($data);
    }

    /**
     * Compact one-line summary for list/export rows.
     */
    public function summary(AuditLog $log): string
    {
        if ($log->reason) {
            return \Illuminate\Support\Str::limit($log->reason, 80);
        }

        $parts = array_filter([
            $log->entity_type,
            $log->entity_id ? '#'.$log->entity_id : null,
        ]);

        return implode(' ', $parts) ?: '-';
    }

    /**
     * @return array<string, mixed>
     */
    private function resolveClientContext(?Request $request): array
    {
        if ($request) {
            $context = $this->clientContextResolver->fromRequest($request);

            if ($context !== []) {
                return $context;
            }
        }

        $sessionContext = session('audit.client_context');

        if (is_array($sessionContext) && $sessionContext !== []) {
            if ($request) {
                $fresh = $this->clientContextResolver->fromRequest($request);

                return array_merge($sessionContext, array_filter([
                    'ip_address' => $fresh['ip_address'] ?? null,
                    'user_agent' => $fresh['user_agent'] ?? null,
                ]));
            }

            return $sessionContext;
        }

        return $request ? $this->clientContextResolver->fromRequest($request) : [];
    }

    private function computeHash(array $payload): string
    {
        return hash('sha256', json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }

    private function latestRecordHash(): string
    {
        if (! $this->supportsHashChain()) {
            return config('audit.genesis_hash');
        }

        $latest = AuditLog::query()->orderByDesc('id')->value('record_hash');

        return $latest ?? config('audit.genesis_hash');
    }

    private function supportsHashChain(): bool
    {
        return $this->isAvailable()
            && Schema::hasColumn('audit_logs', 'record_hash')
            && Schema::hasColumn('audit_logs', 'previous_hash');
    }

    private function hasClientContextColumn(): bool
    {
        return $this->isAvailable() && Schema::hasColumn('audit_logs', 'client_context');
    }

    private function isAvailable(): bool
    {
        try {
            return Schema::hasTable('audit_logs');
        } catch (\Throwable) {
            return false;
        }
    }
}
