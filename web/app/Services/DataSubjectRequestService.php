<?php

namespace App\Services;

use App\Models\DataSubjectRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class DataSubjectRequestService
{
    public function __construct(
        protected AuditService $auditService,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function createFromPublic(array $data, Request $request): DataSubjectRequest
    {
        if (! Schema::hasTable('data_subject_requests')) {
            throw new \RuntimeException('Data subject requests are not available yet.');
        }

        $receivedAt = now();
        $slaDays = (int) config('data-protection.data_subject_request_sla_days', 30);

        $record = DataSubjectRequest::create([
            'request_number' => $this->generateRequestNumber(),
            'requester_name' => $data['requester_name'],
            'requester_email' => $data['requester_email'],
            'requester_phone' => $data['requester_phone'] ?? null,
            'request_type' => $data['request_type'],
            'description' => $data['description'] ?? null,
            'status' => 'received',
            'received_at' => $receivedAt,
            'due_at' => $receivedAt->copy()->addDays($slaDays),
        ]);

        $this->auditService->log(
            'data_protection.dsr.received',
            'data_subject_request',
            $record->id,
            null,
            [
                'request_number' => $record->request_number,
                'request_type' => $record->request_type,
                'requester_email' => $record->requester_email,
            ],
            'Public data subject request submitted',
            'success',
            null,
            $request,
        );

        return $record;
    }

    public function markInProgress(DataSubjectRequest $record, int $userId, ?Request $request = null): DataSubjectRequest
    {
        $record->update([
            'status' => 'in_progress',
            'handled_by' => $userId,
        ]);

        $this->auditService->log(
            'data_protection.dsr.in_progress',
            'data_subject_request',
            $record->id,
            null,
            ['request_number' => $record->request_number],
            'Data subject request marked in progress',
            'success',
            null,
            $request,
        );

        return $record->fresh();
    }

    public function complete(DataSubjectRequest $record, int $userId, ?string $notes = null, ?Request $request = null): DataSubjectRequest
    {
        $record->update([
            'status' => 'completed',
            'handled_by' => $userId,
            'completed_at' => now(),
            'outcome_notes' => $notes,
        ]);

        $this->auditService->log(
            'data_protection.dsr.completed',
            'data_subject_request',
            $record->id,
            null,
            [
                'request_number' => $record->request_number,
                'has_outcome_notes' => filled($notes),
            ],
            'Data subject request completed',
            'success',
            null,
            $request,
        );

        return $record->fresh();
    }

    private function generateRequestNumber(): string
    {
        do {
            $number = 'DSR-'.now()->format('Y').'-'.strtoupper(Str::random(6));
        } while (DataSubjectRequest::query()->where('request_number', $number)->exists());

        return $number;
    }
}
