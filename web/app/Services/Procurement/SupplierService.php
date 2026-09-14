<?php

namespace App\Services\Procurement;

use App\Models\Rfq;
use App\Models\RfqQuotation;
use App\Models\RfqSupplier;
use App\Models\Supplier;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class SupplierService
{
    public function generateSupplierCode(): string
    {
        $year = now()->year;
        $prefix = "SUP-{$year}-";

        $lastNumber = Supplier::query()
            ->where('supplier_code', 'like', $prefix . '%')
            ->orderByDesc('supplier_code')
            ->value('supplier_code');

        if ($lastNumber) {
            $parts = explode('-', $lastNumber);
            $lastSeq = (int) end($parts);
        } else {
            $lastSeq = 0;
        }

        $nextSeq = $lastSeq + 1;

        return $prefix . str_pad((string) $nextSeq, 4, '0', STR_PAD_LEFT);
    }

    public function registerSupplier(array $data): Supplier
    {
        $data['supplier_code'] = $this->generateSupplierCode();
        $data['compliance_status'] = 'pending';
        $data['performance_score'] = 50.00;
        $data['blacklist_status'] = 'active';
        $data['is_active'] = 1;

        return DB::transaction(function () use ($data) {
            $supplier = Supplier::query()->create($data);

            return $supplier->fresh();
        });
    }

    public function verifyCompliance(Supplier $supplier, array $data): Supplier
    {
        $update = [
            'compliance_status' => $data['compliance_status'] ?? 'pending',
            'risk_rating' => $data['risk_rating'] ?? $supplier->risk_rating,
            'supplier_category' => $data['supplier_category'] ?? $supplier->supplier_category,
            'sub_category' => $data['sub_category'] ?? $supplier->sub_category,
        ];

        if (!empty($data['compliance_doc_path'])) {
            $update['compliance_doc_path'] = $data['compliance_doc_path'];
        }
        if (!empty($data['pin_certificate_path'])) {
            $update['pin_certificate_path'] = $data['pin_certificate_path'];
        }
        if (!empty($data['cr12_path'])) {
            $update['cr12_path'] = $data['cr12_path'];
        }
        if (!empty($data['audited_financial_statements_path'])) {
            $update['audited_financial_statements_path'] = $data['audited_financial_statements_path'];
        }
        if (!empty($data['notes'])) {
            $update['notes'] = $data['notes'];
        }

        $supplier->update($update);

        return $supplier->fresh();
    }

    public function blacklist(Supplier $supplier, ?string $reason = null, ?int $staffId = null): Supplier
    {
        $supplier->update([
            'blacklist_status' => 'blacklisted',
            'blacklist_reason' => $reason,
            'blacklisted_at' => now(),
            'blacklisted_by' => $staffId ?? Auth::id(),
        ]);

        return $supplier->fresh();
    }

    public function removeBlacklist(Supplier $supplier): Supplier
    {
        $supplier->update([
            'blacklist_status' => 'active',
            'blacklist_reason' => null,
            'blacklisted_at' => null,
            'blacklisted_by' => null,
        ]);

        return $supplier->fresh();
    }

    public function updatePerformance(Supplier $supplier, float $newScore, ?int $updatedBy = null): Supplier
    {
        $rollingScores = $supplier->past_contracts ?? [];
        $rollingScores[] = [
            'score' => $newScore,
            'updated_at' => now()->toDateTimeString(),
        ];

        $last12 = array_slice($rollingScores, -12);
        $average = array_sum(array_column($last12, 'score')) / count($last12);

        $supplier->update([
            'performance_score' => round($average, 2),
            'performance_last_updated_at' => now(),
            'performance_updated_by' => $updatedBy ?? Auth::id(),
            'past_contracts' => $rollingScores,
        ]);

        if ($average < 20) {
            $this->blacklist($supplier, 'Performance score below 20/100 threshold');
        } elseif ($average < 30) {
            $supplier->update(['risk_rating' => 'high']);
        } elseif ($average < 50) {
            $supplier->update(['risk_rating' => 'medium']);
        } else {
            $supplier->update(['risk_rating' => 'low']);
        }

        return $supplier->fresh();
    }

    public function getEligibleSuppliersForRfq(Rfq $rfq, int $minCount = 3): array
    {
        $categories = $rfq->minimum_categories ?? [];

        $query = Supplier::query()
            ->active()
            ->compliant()
            ->when($categories !== [], function ($q) use ($categories) {
                $q->whereIn('supplier_category', $categories);
            })
            ->whereNotIn('id', function ($sub) use ($rfq) {
                $sub->select('supplier_id')->from('rfq_suppliers')->where('rfq_id', $rfq->id);
            })
            ->orderByDesc('performance_score');

        return $query->limit($minCount + 5)->get()->all();
    }

    public function inviteSuppliers(Rfq $rfq, array $supplierIds): array
    {
        $invited = [];

        foreach ($supplierIds as $supplierId) {
            $invited[] = RfqSupplier::query()->create([
                'rfq_id' => $rfq->id,
                'supplier_id' => $supplierId,
                'invitation_status' => 'invited',
                'invited_at' => now(),
            ]);
        }

        return $invited;
    }

    public function closeRfq(Rfq $rfq): Rfq
    {
        if ($rfq->isClosed() || $rfq->isAwarded()) {
            throw new InvalidArgumentException('RFQ is already closed or awarded.');
        }

        $rfq->update([
            'status' => 'closed',
            'closed_at' => now(),
        ]);

        RfqQuotation::query()
            ->where('rfq_id', $rfq->id)
            ->where('status', 'submitted')
            ->update(['status' => 'locked', 'locked_at' => now()]);

        return $rfq->fresh();
    }

    public function awardRfq(Rfq $rfq, Supplier $supplier, ?string $notes = null): Rfq
    {
        if (!$rfq->isClosed()) {
            throw new InvalidArgumentException('RFQ must be closed before award.');
        }

        $quotation = RfqQuotation::query()
            ->where('rfq_id', $rfq->id)
            ->where('supplier_id', $supplier->id)
            ->where('status', 'locked')
            ->first();

        if (!$quotation) {
            throw new InvalidArgumentException('No locked quotation found for the selected supplier.');
        }

        $rfq->update([
            'status' => 'awarded',
            'award_decision' => 'awarded',
            'awarded_supplier_id' => $supplier->id,
            'awarded_amount' => $quotation->total_price,
            'approval_status' => 'pending',
        ]);

        return $rfq->fresh();
    }

    public function compileEvaluationScores(Rfq $rfq): array
    {
        $evaluations = RfqEvaluation::query()
            ->with(['supplier', 'evaluator'])
            ->where('rfq_id', $rfq->id)
            ->where('is_recused', false)
            ->get();

        $scores = [];

        foreach ($evaluations as $evaluation) {
            $supplierId = $evaluation->supplier_id;

            if (!isset($scores[$supplierId])) {
                $scores[$supplierId] = [
                    'supplier' => $evaluation->supplier,
                    'price_score' => 0,
                    'technical_score' => 0,
                    'delivery_score' => 0,
                    'payment_terms_score' => 0,
                    'performance_score' => 0,
                    'total_score' => 0,
                    'count' => 0,
                ];
            }

            $scores[$supplierId]['price_score'] += (float) $evaluation->price_score;
            $scores[$supplierId]['technical_score'] += (float) $evaluation->technical_score;
            $scores[$supplierId]['delivery_score'] += (float) $evaluation->delivery_score;
            $scores[$supplierId]['payment_terms_score'] += (float) $evaluation->payment_terms_score;
            $scores[$supplierId]['performance_score'] += (float) $evaluation->performance_score;
            $scores[$supplierId]['total_score'] += (float) $evaluation->total_score;
            $scores[$supplierId]['count']++;
        }

        foreach ($scores as $supplierId => $score) {
            $count = max($score['count'], 1);
            $scores[$supplierId] = [
                'supplier' => $score['supplier'],
                'price_score' => round($score['price_score'] / $count, 2),
                'technical_score' => round($score['technical_score'] / $count, 2),
                'delivery_score' => round($score['delivery_score'] / $count, 2),
                'payment_terms_score' => round($score['payment_terms_score'] / $count, 2),
                'performance_score' => round($score['performance_score'] / $count, 2),
                'total_score' => round($score['total_score'] / $count, 2),
            ];
        }

        uasort($scores, fn ($a, $b) => $b['total_score'] <=> $a['total_score']);

        $rank = 1;
        foreach (array_keys($scores) as $supplierId) {
            $scores[$supplierId]['rank'] = $rank++;
        }

        return $scores;
    }
}
