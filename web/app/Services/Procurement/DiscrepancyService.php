<?php

namespace App\Services\Procurement;

use App\Models\Discrepancy;
use App\Models\ProcurementInvoice;
use App\Models\CreditNote;
use App\Models\Supplier;
use App\Models\ThreeWayMatch;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Carbon;

class DiscrepancyService
{
    public const SUPPLIER_RESPONSE_DAYS = 5;

    public function raise(ProcurementInvoice $invoice, ThreeWayMatch $match, array $data): Discrepancy
    {
        return DB::transaction(function () use ($invoice, $match, $data) {
            $discrepancy = Discrepancy::create([
                'invoice_id' => $invoice->id,
                'three_way_match_id' => $match->id,
                'supplier_id' => $invoice->supplier_id,
                'discrepancy_type' => $data['type'] ?? 'general',
                'field_name' => $data['field'] ?? null,
                'po_value' => $data['po_value'] ?? null,
                'quotation_value' => $data['quotation_value'] ?? null,
                'invoice_value' => $data['invoice_value'] ?? null,
                'deviation_amount' => $data['deviation_amount'] ?? 0,
                'status' => 'open',
                'raised_by' => Auth::id(),
                'is_escalated' => false,
                'is_fraud_suspected' => false,
            ]);

            $invoice->status = 'discrepancy_flagged';
            $invoice->save();
            $match->status = 'discrepancy';
            $match->save();

            return $discrepancy;
        });
    }

    public function sendSupplierQuery(Discrepancy $discrepancy): bool
    {
        return DB::transaction(function () use ($discrepancy) {
            $discrepancy->update([
                'status' => 'awaiting_supplier',
                'supplier_response_at' => Carbon::now()->addDays(self::SUPPLIER_RESPONSE_DAYS),
            ]);

            $supplier = Supplier::find($discrepancy->supplier_id);
            if ($supplier && $supplier->email) {
                // Mail::to($supplier->email)->send(new \App\Mail\DiscrepancyQuery($discrepancy));
            }
            return true;
        });
    }

    public function resolve(Discrepancy $discrepancy, array $data): Discrepancy
    {
        return DB::transaction(function () use ($discrepancy, $data) {
            $discrepancy->update([
                'status' => 'resolved',
                'resolved_by' => Auth::id(),
                'resolved_at' => now(),
                'resolution_note' => $data['resolution_note'] ?? '',
                'resolution_type' => $data['resolution_type'] ?? 'accepted',
                'supplier_response' => $data['supplier_response'] ?? null,
                'supplier_response_at' => now(),
            ]);

            $this->updateInvoiceStatus($discrepancy->invoice);

            return $discrepancy;
        });
    }

    public function escalate(Discrepancy $discrepancy, array $data): Discrepancy
    {
        return DB::transaction(function () use ($discrepancy, $data) {
            $discrepancy->update([
                'status' => 'escalated',
                'is_escalated' => true,
                'escalated_to' => $data['escalated_to'] ?? null,
                'escalation_note' => $data['escalation_note'] ?? '',
                'escalated_at' => now(),
            ]);

            return $discrepancy;
        });
    }

    public function flagForReview(Discrepancy $discrepancy): Discrepancy
    {
        return DB::transaction(function () use ($discrepancy) {
            $discrepancy->update([
                'status' => 'open',
                'is_fraud_suspected' => true,
            ]);
            return $discrepancy;
        });
    }

    public function issueCreditNote(Discrepancy $discrepancy, array $data): CreditNote
    {
        return DB::transaction(function () use ($discrepancy, $data) {
            $creditNote = CreditNote::create([
                'discrepancy_id' => $discrepancy->id,
                'invoice_id' => $discrepancy->invoice_id,
                'supplier_id' => $discrepancy->supplier_id,
                'credit_note_number' => $this->generateCreditNoteNumber(),
                'amount' => $data['amount'],
                'reason' => $data['reason'] ?? '',
                'status' => 'issued',
                'created_by' => Auth::id(),
            ]);

            $discrepancy->update([
                'resolution_type' => 'credit_note_issued',
                'credit_note_id' => $creditNote->id,
            ]);

            return $creditNote;
        });
    }

    private function updateInvoiceStatus(ProcurementInvoice $invoice): void
    {
        $openDiscrepancies = $invoice->discrepancies()->where('status', 'open')->count();
        if ($openDiscrepancies === 0) {
            $invoice->status = 'matched';
            $invoice->save();
        }
    }

    private function generateCreditNoteNumber(): string
    {
        $year = now()->format('Y');
        $count = CreditNote::whereYear('created_at', $year)->count() + 1;
        return "CN-{$year}-" . str_pad($count, 4, '0', STR_PAD_LEFT);
    }
}
