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

class PaymentVerificationSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(PaymentVerificationDemoDataSeeder::class);
    }
}
