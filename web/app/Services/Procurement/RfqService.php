<?php

namespace App\Services\Procurement;

use App\Models\Rfq;
use App\Models\RfqQuotation;
use App\Models\RfqSupplier;
use InvalidArgumentException;

class RfqService
{
    public function calculatePriceScore(Rfq $rfq, RfqQuotation $quotation): float
    {
        $quotations = RfqQuotation::query()
            ->where('rfq_id', $rfq->id)
            ->where('status', 'locked')
            ->orderByDesc('total_price')
            ->get(['id', 'total_price']);

        if ($quotations->isEmpty()) {
            return 0;
        }

        $minPrice = $quotations->min('total_price');
        $maxPrice = $quotations->max('total_price');

        if ($maxPrice === $minPrice) {
            return 40;
        }

        $score = 40 * (1 - ($quotation->total_price - $minPrice) / ($maxPrice - $minPrice));

        return max(0, min(40, round($score, 2)));
    }

    public function calculateDeliveryScore(RfqQuotation $quotation): float
    {
        $period = strtolower(trim($quotation->delivery_period ?? ''));

        if ($period === '') {
            return 5;
        }

        if (preg_match('/(\d+)\s*day/', $period, $matches)) {
            $days = (int) $matches[1];

            if ($days <= 1) {
                return 15;
            }

            if ($days <= 7) {
                return 12;
            }

            if ($days <= 14) {
                return 9;
            }

            if ($days <= 30) {
                return 6;
            }

            return 3;
        }

        return 5;
    }

    public function calculatePaymentTermsScore(RfqQuotation $quotation): float
    {
        $terms = strtolower(trim($quotation->payment_terms ?? ''));

        if ($terms === '') {
            return 5;
        }

        if (str_contains($terms, 'advance') || str_contains($terms, 'prepayment')) {
            return 4;
        }

        if (str_contains($terms, 'net 30') || str_contains($terms, '30 days')) {
            return 8;
        }

        if (str_contains($terms, 'net 60') || str_contains($terms, '60 days')) {
            return 6;
        }

        if (str_contains($terms, 'net 15') || str_contains($terms, '15 days')) {
            return 10;
        }

        if (str_contains($terms, 'cash on delivery') || str_contains($terms, 'cod')) {
            return 10;
        }

        return 5;
    }

    public function submitEvaluation(array $data): RfqEvaluation
    {
        $rfq = Rfq::query()->findOrFail($data['rfq_id']);
        $quotation = RfqQuotation::query()
            ->where('rfq_id', $rfq->id)
            ->where('supplier_id', $data['supplier_id'])
            ->where('status', 'locked')
            ->firstOrFail();

        $priceScore = $this->calculatePriceScore($rfq, $quotation);
        $deliveryScore = $this->calculateDeliveryScore($quotation);
        $paymentTermsScore = $this->calculatePaymentTermsScore($quotation);

        $technicalScore = min(30, max(0, (float) ($data['technical_score'] ?? 0)));
        $performanceScore = min(5, max(0, (float) ($data['performance_score'] ?? 0)));

        $totalScore = $priceScore + $technicalScore + $deliveryScore + $paymentTermsScore + $performanceScore;

        return RfqEvaluation::query()->create([
            'rfq_id' => $rfq->id,
            'supplier_id' => $data['supplier_id'],
            'evaluated_by' => $data['evaluated_by'] ?? auth()->id(),
            'price_score' => $priceScore,
            'technical_score' => $technicalScore,
            'delivery_score' => $deliveryScore,
            'payment_terms_score' => $paymentTermsScore,
            'performance_score' => $performanceScore,
            'total_score' => $totalScore,
            'comments' => $data['comments'] ?? null,
            'has_conflict_of_interest' => $data['has_conflict_of_interest'] ?? false,
            'conflict_of_interest_details' => $data['conflict_of_interest_details'] ?? null,
            'is_recused' => $data['has_conflict_of_interest'] ?? false,
            'evaluated_at' => now(),
        ]);
    }

    public function approveAward(Rfq $rfq, ?string $notes = null): Rfq
    {
        if ($rfq->approval_status !== 'pending') {
            throw new InvalidArgumentException('Award is not pending approval.');
        }

        $rfq->update([
            'approval_status' => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'approval_notes' => $notes,
        ]);

        return $rfq->fresh();
    }

    public function rejectAward(Rfq $rfq, ?string $notes = null): Rfq
    {
        if ($rfq->approval_status !== 'pending') {
            throw new InvalidArgumentException('Award is not pending approval.');
        }

        $rfq->update([
            'approval_status' => 'rejected',
            'award_decision' => 'cancelled',
            'approval_notes' => $notes,
        ]);

        return $rfq->fresh();
    }
}
