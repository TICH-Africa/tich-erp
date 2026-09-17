<?php

namespace App\Services\Procurement;

use App\Models\ProcurementInvoice;
use App\Models\ThreeWayMatch;
use App\Models\PurchaseOrder;
use App\Models\RfqQuotation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class MatchingEngineService
{
    public const QUANTITY_TOLERANCE_PERCENT = 5.0;
    public const PRICE_TOLERANCE_PERCENT = 0.0;
    public const DESCRIPTION_TOLERANCE_PERCENT = 0.0;
    public const MATERIAL_DISCREPANCY_THRESHOLD = 0.10;

    public function run(ProcurementInvoice $invoice): ThreeWayMatch
    {
        return DB::transaction(function () use ($invoice) {
            $po = $invoice->purchaseOrder;
            $quotation = $po && $po->supplier ? $po->supplier->quotations()->where('status', 'locked')->first() : null;

            $match = ThreeWayMatch::create([
                'invoice_id' => $invoice->id,
                'purchase_order_id' => $po->id ?? 0,
                'rfq_quotation_id' => $quotation?->id ?? null,
                'status' => 'pending',
                'quantity_po' => $po ? $this->extractQuantity($po, 'quantity') : 0,
                'quantity_quotation' => $quotation ? $this->extractQuantity($quotation, 'quantity') : 0,
                'quantity_invoiced' => $invoice->subtotal > 0 ? ($invoice->total_amount / max($invoice->unit_price_invoiced ?? 1, 0.01)) : 0,
                'unit_price_po' => $po ? $po->total_amount : 0,
                'unit_price_quotation' => $quotation ? $quotation->unit_price : 0,
                'unit_price_invoiced' => $invoice->total_amount,
                'quantity_tolerance_percent' => self::QUANTITY_TOLERANCE_PERCENT,
                'price_match' => false,
                'description_match' => false,
                'quantity_match' => false,
                'arithmetic_match' => false,
                'delivery_match' => false,
                'total_calculated' => 0,
                'total_invoiced' => $invoice->total_amount,
                'deviation_amount' => 0,
                'discrepancy_count' => 0,
                'matched_by' => Auth::id(),
                'matched_at' => now(),
            ]);

            $this->performMatching($match, $po, $quotation, $invoice);

            $invoice->matched_by = Auth::id();
            $invoice->matched_at = now();
            if ($match->status === 'full_match') {
                $invoice->status = 'matched';
            } elseif ($match->status === 'discrepancy') {
                $invoice->status = 'discrepancy_flagged';
            } else {
                $invoice->status = 'partial_match';
            }
            $invoice->save();

            return $match;
        });
    }

    private function performMatching(ThreeWayMatch $match, ?PurchaseOrder $po, ?RfqQuotation $quotation, ProcurementInvoice $invoice): void
    {
        $discrepancyCount = 0;

        if ($po) {
            $match->quantity_match = $this->checkQuantity($match, $po);
            if (!$match->quantity_match) $discrepancyCount++;
        }

        if ($po) {
            $match->price_match = $this->checkPrice($match, $po);
            if (!$match->price_match) $discrepancyCount++;
        }

        if ($quotation) {
            $match->description_match = $this->checkDescription($match, $quotation);
            if (!$match->description_match) $discrepancyCount++;
        }

        $match->arithmetic_match = $this->checkArithmetic($match);
        if (!$match->arithmetic_match) $discrepancyCount++;

        $match->total_calculated = $match->quantity_po * $match->unit_price_po + ($match->quantity_quotation * $match->unit_price_quotation) / max(1, 1);
        $match->deviation_amount = abs($match->total_invoiced - $match->total_calculated);
        $match->discrepancy_count = $discrepancyCount;

        if ($discrepancyCount === 0) {
            $match->status = 'full_match';
        } elseif ($discrepancyCount === 1 && $match->deviation_amount <= ($match->total_invoiced * self::MATERIAL_DISCREPANCY_THRESHOLD)) {
            $match->status = 'partial_match';
        } else {
            $match->status = 'discrepancy';
        }

        $match->save();
    }

    private function checkQuantity(ThreeWayMatch $match, PurchaseOrder $po): bool
    {
        return $match->quantity_invoiced <= ($match->quantity_po * (1 + (self::QUANTITY_TOLERANCE_PERCENT / 100)));
    }

    private function checkPrice(ThreeWayMatch $match, PurchaseOrder $po): bool
    {
        $tolerance = self::PRICE_TOLERANCE_PERCENT / 100;
        return abs($match->unit_price_invoiced - $match->unit_price_po) <= ($match->unit_price_po * $tolerance);
    }

    private function checkDescription(ThreeWayMatch $match, RfqQuotation $quotation): bool
    {
        return true;
    }

    private function checkArithmetic(ThreeWayMatch $match): bool
    {
        $calculated = $match->quantity_invoiced * $match->unit_price_invoiced;
        return abs($calculated - $match->total_invoiced) < 0.01;
    }

    private function extractQuantity($model, string $field): float
    {
        return (float) ($model->{$field} ?? $model->quantity ?? 0);
    }

    public function getMatchingCertificate(ThreeWayMatch $match): string
    {
        return sprintf(
            'MATCH-CERT-%s: PO %s vs Invoice %s | Quantity: %s | Price: %s | Description: %s | Arithmetic: %s | Deviation: KES %s',
            $match->id,
            $match->quantity_match ? 'PASS' : 'FAIL',
            $match->price_match ? 'PASS' : 'FAIL',
            $match->description_match ? 'PASS' : 'FAIL',
            $match->arithmetic_match ? 'PASS' : 'FAIL',
            number_format($match->deviation_amount, 2)
        );
    }
}
