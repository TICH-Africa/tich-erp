<?php

namespace Database\Seeders;

use App\Models\Supplier;
use App\Models\PurchaseOrder;
use App\Models\RfqQuotation;
use App\Models\ProcurementInvoice;
use App\Models\ProcurementPayment;
use App\Models\ThreeWayMatch;
use App\Models\Discrepancy;
use App\Models\CreditNote;
use App\Models\PaymentAudit;
use App\Models\Staff;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class PaymentVerificationDemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->truncate();

        $suppliers = $this->getSuppliers();
        $pos = $this->getPurchaseOrders($suppliers);
        $rfqs = $this->getRfqs($suppliers);
        $staff = Staff::inRandomOrder()->limit(5)->get();

        $invoices = $this->createInvoices($suppliers, $pos, $rfqs, $staff);
        $this->createMatches($invoices, $pos);
        $this->createDiscrepancies($invoices);
        $this->createPayments($invoices);
        $this->createCreditNotes($invoices);
        $this->createAuditTrail($invoices, $staff);
    }

    private function truncate(): void
    {
        DB::table('payment_audits')->delete();
        DB::table('credit_notes')->delete();
        DB::table('discrepancies')->delete();
        DB::table('three_way_matches')->delete();
        DB::table('procurement_payments')->delete();
        DB::table('procurement_invoices')->delete();
    }

    private function getSuppliers(): \Illuminate\Support\Collection
    {
        $suppliers = Supplier::active()->limit(6)->get();
        if ($suppliers->isEmpty()) {
            $suppliers = Supplier::limit(6)->get();
        }
        return $suppliers;
    }

    private function getPurchaseOrders($suppliers): \Illuminate\Support\Collection
    {
        $pos = PurchaseOrder::where('status', 'approved')->with('supplier')->limit(6)->get();
        if ($pos->isEmpty()) {
            $pos = PurchaseOrder::with('supplier')->limit(6)->get();
        }
        return $pos;
    }

    private function getRfqs($suppliers): \Illuminate\Support\Collection
    {
        $rfqs = RfqQuotation::where('status', 'locked')->with('rfq')->limit(6)->get();
        return $rfqs;
    }

    private function createInvoices($suppliers, $pos, $rfqs, $staff): \Illuminate\Support\Collection
    {
        $statuses = ['pending_matching', 'matched', 'discrepancy_flagged', 'matched', 'matched', 'pending_matching'];
        $invoices = collect();

        foreach ($pos as $i => $po) {
            $supplier = $suppliers[$i % $suppliers->count()];
            $rfq = $rfqs->get($i);
            $totalAmount = rand(30000, 180000) / 10;
            $taxAmount = round($totalAmount * 0.16, 2);
            $retentionPercent = in_array($i, [1, 3]) ? 10.00 : 0.00;
            $retentionAmount = round($totalAmount * ($retentionPercent / 100), 2);

            $invoice = ProcurementInvoice::create([
                'invoice_number' => "INV-2026-" . str_pad(217 + $i, 4, '0', STR_PAD_LEFT),
                'supplier_id' => $supplier->id,
                'purchase_order_id' => $po->id,
                'rfq_id' => $rfq?->id,
                'requisition_id' => $po->requisition_id,
                'invoice_date' => Carbon::now()->subDays(10 - $i)->toDateString(),
                'due_date' => Carbon::now()->addDays(30)->toDateString(),
                'subtotal' => $totalAmount,
                'tax_amount' => $taxAmount,
                'total_amount' => $totalAmount + $taxAmount,
                'amount_paid' => in_array($i, [2, 4]) ? round($totalAmount * 0.5, 2) : 0,
                'balance' => $totalAmount + $taxAmount,
                'status' => $statuses[$i] ?? 'pending_matching',
                'retention_percent' => $retentionPercent,
                'retention_amount' => $retentionAmount,
                'released_amount' => in_array($i, [2, 4]) ? round($totalAmount * 0.5, 2) : 0,
                'payment_certificate' => $statuses[$i] === 'matched' ? "MATCH-CERT-{$i}: PASS" : null,
                'notes' => "Test invoice #{$i}",
                'created_by' => $staff->get($i % $staff->count())->id ?? 1,
                'matched_by' => $statuses[$i] !== 'pending_matching' ? $staff->get(($i + 1) % $staff->count())->id ?? 1 : null,
                'matched_at' => $statuses[$i] !== 'pending_matching' ? Carbon::now()->subDays(5 - $i) : null,
                'paid_at' => in_array($i, [2, 4]) ? Carbon::now()->subDays(2) : null,
            ]);

            $invoice->balance = $invoice->total_amount - $invoice->amount_paid - $invoice->released_amount;
            $invoice->save();
            $invoices->push($invoice);
        }

        return $invoices;
    }

    private function createMatches($invoices, $pos): void
    {
        foreach ($invoices as $i => $invoice) {
            $po = $pos->get($i % $pos->count());
            $statuses = ['pending', 'full_match', 'discrepancy', 'partial_match', 'full_match', 'pending'];
            ThreeWayMatch::create([
                'invoice_id' => $invoice->id,
                'purchase_order_id' => $po->id,
                'rfq_quotation_id' => $invoice->rfq_id,
                'status' => $statuses[$i] ?? 'pending',
                'quantity_po' => 100,
                'quantity_quotation' => 100,
                'quantity_invoiced' => rand(80, 120),
                'unit_price_po' => $invoice->total_amount,
                'unit_price_quotation' => $invoice->total_amount * 0.98,
                'unit_price_invoiced' => $invoice->total_amount,
                'quantity_tolerance_percent' => 5.00,
                'price_match' => $i !== 2,
                'description_match' => true,
                'quantity_match' => $i !== 2 && $i !== 3,
                'arithmetic_match' => $i !== 2,
                'delivery_match' => true,
                'total_calculated' => $invoice->total_amount * 0.95,
                'total_invoiced' => $invoice->total_amount,
                'deviation_amount' => in_array($i, [2, 3]) ? rand(1000, 5000) / 10 : 0,
                'discrepancy_count' => in_array($i, [2, 3]) ? rand(1, 3) : 0,
                'matched_by' => $invoice->matched_by,
                'matched_at' => $invoice->matched_at,
                'matching_certificate' => $invoice->payment_certificate,
                'notes' => "Auto-generated match for invoice #{$i}",
            ]);
        }
    }

    private function createDiscrepancies($invoices): void
    {
        $types = ['price_mismatch', 'quantity_overrun', 'description_mismatch', 'arithmetic', 'missing_delivery_note'];

        foreach ($invoices as $i => $invoice) {
            if (in_array($i, [2, 3, 5])) {
                Discrepancy::create([
                    'invoice_id' => $invoice->id,
                    'three_way_match_id' => ThreeWayMatch::where('invoice_id', $invoice->id)->first()?->id,
                    'supplier_id' => $invoice->supplier_id,
                    'discrepancy_type' => $types[$i % count($types)],
                    'field_name' => 'unit_price',
                    'po_value' => 'KES ' . number_format($invoice->total_amount, 2),
                    'quotation_value' => 'KES ' . number_format($invoice->total_amount * 0.98, 2),
                    'invoice_value' => 'KES ' . number_format($invoice->total_amount * 1.02, 2),
                    'deviation_amount' => rand(1000, 8000) / 10,
                    'status' => ['open', 'awaiting_supplier', 'escalated', 'resolved', 'open', 'open'][$i],
                    'raised_by' => $invoice->created_by,
                    'resolved_by' => in_array($i, [3]) ? $invoice->created_by : null,
                    'resolution_note' => in_array($i, [3]) ? 'Accepted supplier explanation' : null,
                    'resolution_type' => in_array($i, [3]) ? 'accepted' : null,
                    'supplier_response' => in_array($i, [2]) ? 'Price adjustment confirmed' : null,
                    'supplier_response_at' => in_array($i, [2]) ? Carbon::now()->subDay() : null,
                    'is_escalated' => $i === 3,
                    'is_fraud_suspected' => false,
                    'escalation_note' => null,
                    'escalated_at' => $i === 3 ? Carbon::now()->subDay() : null,
                ]);
            }
        }
    }

    private function createPayments($invoices): void
    {
        $stkRequests = [];

        foreach ($invoices as $i => $invoice) {
            if (in_array($i, [1, 2, 4])) {
                $method = ['mpesa', 'bank_transfer', 'mpesa'][$i];
                $mpesaStkId = null;

                if ($method === 'mpesa') {
                    $stkRequest = \App\Models\MpesaStkRequest::create([
                        'invoice_id' => $invoice->id,
                        'amount' => $invoice->balance,
                        'phone' => $invoice->supplier->phone ?? '254700000000',
                        'account_reference' => "PAY-2026-" . str_pad(100 + $i, 4, '0', STR_PAD_LEFT),
                        'checkout_request_id' => "CO-" . uniqid(),
                        'merchant_request_id' => "MR-" . uniqid(),
                        'status' => 'pending',
                    ]);
                    $mpesaStkId = $stkRequest->id;
                    $stkRequests[] = $stkRequest;
                }

                ProcurementPayment::create([
                    'invoice_id' => $invoice->id,
                    'payment_number' => "PAY-2026-" . str_pad(100 + $i, 4, '0', STR_PAD_LEFT),
                    'supplier_id' => $invoice->supplier_id,
                    'amount' => $invoice->balance,
                    'retention_amount' => $invoice->retention_amount,
                    'released_amount' => $invoice->balance - $invoice->retention_amount,
                    'payment_method' => $method,
                    'payment_reference' => "REF-{$i}",
                    'transaction_channel_ref' => "TXN-{$i}",
                    'status' => ['pending', 'success', 'stk_pending'][$i],
                    'mpesa_stk_request_id' => $mpesaStkId,
                    'recorded_by' => $invoice->created_by,
                    'payment_date' => Carbon::now()->subDays(3 - $i),
                ]);
            }
        }
    }

    private function createCreditNotes($invoices): void
    {
        foreach ($invoices as $i => $invoice) {
            if ($i === 3) {
                $disc = Discrepancy::where('invoice_id', $invoice->id)->first();
                if ($disc) {
                    CreditNote::create([
                        'discrepancy_id' => $disc->id,
                        'invoice_id' => $invoice->id,
                        'supplier_id' => $invoice->supplier_id,
                        'credit_note_number' => "CN-2026-" . str_pad(1, 4, '0', STR_PAD_LEFT),
                        'amount' => $invoice->total_amount * 0.1,
                        'reason' => 'Overcharge on unit price',
                        'status' => 'issued',
                        'created_by' => $invoice->created_by,
                    ]);
                }
            }
        }
    }

    private function createAuditTrail($invoices, $staff): void
    {
        foreach ($invoices as $i => $invoice) {
            $staffMember = $staff->get($i % $staff->count());
            $actorName = ($staffMember->first_name ?? '') . ' ' . ($staffMember->surname ?? '');
            PaymentAudit::create([
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'actor' => $actorName ?: 'System',
                'action' => 'Invoice created',
                'result' => "Invoice {$invoice->invoice_number} logged",
                'created_at' => $invoice->created_at,
            ]);

            if ($invoice->matched_at) {
                PaymentAudit::create([
                    'invoice_id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'actor' => 'System',
                    'action' => 'Three-way match completed',
                    'result' => "Status: {$invoice->status}",
                    'created_at' => $invoice->matched_at,
                ]);
            }

            $match = ThreeWayMatch::where('invoice_id', $invoice->id)->first();
            if ($match && $match->status === 'discrepancy') {
                PaymentAudit::create([
                    'invoice_id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'actor' => 'Procurement Officer',
                    'action' => 'Discrepancy raised',
                    'result' => 'Supplier query sent',
                    'created_at' => Carbon::now()->subDays(2),
                ]);
            }
        }
    }
}
