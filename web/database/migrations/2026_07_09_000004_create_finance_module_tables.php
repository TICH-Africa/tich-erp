<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('supplier_code', 30)->unique();
            $table->string('supplier_name', 300);
            $table->string('contact_person', 200)->nullable();
            $table->string('email', 255);
            $table->string('phone', 30);
            $table->string('postal_address', 300)->nullable();
            $table->string('physical_address', 500)->nullable();
            $table->string('kra_pin', 50)->nullable();
            $table->string('tax_compliance_status', 50)->default('pending_review'); // pending_review, compliant, non_compliant
            $table->string('compliance_doc_path', 500)->nullable();
            $table->string('bank_name', 200)->nullable();
            $table->string('bank_account_name', 300)->nullable();
            $table->string('bank_account_number', 50)->nullable();
            $table->string('bank_branch', 200)->nullable();
            $table->string('bank_code', 20)->nullable();
            $table->tinyInteger('is_active')->default(1);
            $table->dateTime('created_at')->useCurrent();
        });

        Schema::create('chart_of_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('account_code', 30)->unique();
            $table->string('account_name', 200);
            $table->string('account_type', 50); // asset, liability, equity, revenue, expense
            $table->string('account_category', 100);
            $table->string('parent_account_code', 30)->nullable();
            $table->tinyInteger('is_active')->default(1);
            $table->tinyInteger('is_system_account')->default(0);
            $table->dateTime('created_at')->useCurrent();
        });

        Schema::create('fee_structures', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('program_id');
            $table->unsignedBigInteger('academic_year_id');
            $table->integer('semester_number');
            $table->decimal('tuition_fee', 12, 2);
            $table->decimal('examination_fee', 12, 2)->default(0.00);
            $table->decimal('library_fee', 12, 2)->default(0.00);
            $table->decimal('activity_fee', 12, 2)->default(0.00);
            $table->decimal('hostel_fee', 12, 2)->default(0.00);
            $table->decimal('medical_insurance_fee', 12, 2)->default(0.00);
            $table->decimal('nursing_clinical_fee', 12, 2)->default(0.00);
            $table->decimal('graduation_fee', 12, 2)->default(0.00);
            $table->decimal('registration_fee', 12, 2)->default(0.00);
            $table->json('other_fees')->nullable();
            $table->decimal('total_semester_fee', 12, 2)->default(0.00);
            $table->tinyInteger('is_approved')->default(0);
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->dateTime('approved_at')->nullable();
            $table->date('effective_from');
            $table->tinyInteger('is_active')->default(1);
            $table->unique(['program_id', 'academic_year_id', 'semester_number'], 'fee_structures_unique');
            $table->foreign('program_id')->references('id')->on('academic_programs')->restrictOnDelete();
            $table->foreign('academic_year_id')->references('id')->on('academic_years')->restrictOnDelete();
            $table->foreign('approved_by')->references('id')->on('staff')->nullOnDelete();
        });

        Schema::create('student_accounts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('academic_year_id');
            $table->decimal('total_chargeable', 12, 2)->default(0.00);
            $table->decimal('total_paid', 12, 2)->default(0.00);
            $table->decimal('outstanding_balance', 12, 2)->default(0.00);
            $table->decimal('work_study_credit', 12, 2)->default(0.00);
            $table->decimal('scholarship_amount', 12, 2)->default(0.00);
            $table->decimal('helb_amount', 12, 2)->default(0.00);
            $table->decimal('sponsor_amount', 12, 2)->default(0.00);
            $table->tinyInteger('is_cleared')->default(0);
            $table->dateTime('cleared_at')->nullable();
            $table->date('last_payment_date')->nullable();
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->nullable()->useCurrentOnUpdate();
            $table->unique(['student_id', 'academic_year_id'], 'student_accounts_unique');
            $table->foreign('student_id')->references('id')->on('students')->restrictOnDelete();
            $table->foreign('academic_year_id')->references('id')->on('academic_years')->restrictOnDelete();
        });

        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number', 50)->unique();
            $table->unsignedBigInteger('student_account_id');
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('semester_id')->nullable();
            $table->string('invoice_type', 50); // tuition, application, supplementary, graduation, hostel, other
            $table->string('description', 500);
            $table->decimal('amount', 12, 2);
            $table->decimal('amount_paid', 12, 2)->default(0.00);
            $table->decimal('balance', 12, 2)->default(0.00);
            $table->date('issue_date');
            $table->date('due_date');
            $table->string('status', 50)->default('issued'); // issued, partial, paid, overdue, waived
            $table->string('payment_gateway_ref', 100)->nullable();
            $table->tinyInteger('is_sent_to_portal')->default(0);
            $table->dateTime('sent_at')->nullable();
            $table->string('waiver_reason', 500)->nullable();
            $table->unsignedBigInteger('waived_by')->nullable();
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->nullable()->useCurrentOnUpdate();
            $table->foreign('student_account_id')->references('id')->on('student_accounts')->restrictOnDelete();
            $table->foreign('student_id')->references('id')->on('students')->restrictOnDelete();
            $table->foreign('semester_id')->references('id')->on('semesters')->nullOnDelete();
            $table->foreign('waived_by')->references('id')->on('staff')->nullOnDelete();
            $table->index('student_id');
            $table->index('status');
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('payment_number', 50)->unique();
            $table->unsignedBigInteger('invoice_id');
            $table->unsignedBigInteger('student_account_id');
            $table->unsignedBigInteger('student_id');
            $table->date('payment_date');
            $table->decimal('amount', 12, 2);
            $table->string('payment_method', 50); // mpesa, bank_transfer, cash, card, cheque, eft, helb, sponsor, work_study_credit
            $table->string('payment_reference', 100)->nullable();
            $table->string('transaction_channel_ref', 100)->nullable();
            $table->tinyInteger('is_reconciled')->default(0);
            $table->unsignedBigInteger('reconciled_by')->nullable();
            $table->dateTime('reconciled_at')->nullable();
            $table->unsignedBigInteger('recorded_by');
            $table->dateTime('created_at')->useCurrent();
            $table->foreign('invoice_id')->references('id')->on('invoices')->restrictOnDelete();
            $table->foreign('student_account_id')->references('id')->on('student_accounts')->restrictOnDelete();
            $table->foreign('student_id')->references('id')->on('students')->restrictOnDelete();
            $table->foreign('reconciled_by')->references('id')->on('staff')->nullOnDelete();
            $table->foreign('recorded_by')->references('id')->on('staff')->restrictOnDelete();
            $table->index(['student_id', 'payment_date']);
            $table->index('payment_reference');
        });

        Schema::create('account_ledger', function (Blueprint $table) {
            $table->id();
            $table->date('ledger_date');
            $table->string('transaction_type', 50); // student_payment, invoice_raised, fee_waiver, payroll_disbursement, supplier_payment, donor_disbursement, statutory_remittance, journal_entry, contra
            $table->string('debit_account_code', 30)->nullable();
            $table->string('credit_account_code', 30)->nullable();
            $table->decimal('debit_amount', 12, 2)->default(0.00);
            $table->decimal('credit_amount', 12, 2)->default(0.00);
            $table->string('narration', 500);
            $table->string('reference_table', 50)->nullable();
            $table->string('reference_id', 50)->nullable();
            $table->string('source_module', 50); // student_fees, payroll, procurement, sacco, donor, other
            $table->tinyInteger('is_reversed')->default(0);
            $table->unsignedBigInteger('reversal_ledger_id')->nullable();
            $table->unsignedBigInteger('recorded_by');
            $table->dateTime('created_at')->useCurrent();
            $table->foreign('recorded_by')->references('id')->on('staff')->restrictOnDelete();
            $table->index('ledger_date');
            $table->index(['reference_table', 'reference_id']);
            $table->index(['debit_account_code', 'credit_account_code']);
        });

        Schema::create('work_study_ledger', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_id');
            $table->string('project_name', 300);
            $table->decimal('hours_logged', 8, 2);
            $table->decimal('hourly_rate', 10, 2);
            $table->decimal('total_earnings', 12, 2)->default(0.00);
            $table->decimal('tuition_offset_amount', 12, 2);
            $table->string('offset_reference', 50)->nullable();
            $table->date('work_date');
            $table->unsignedBigInteger('verified_by');
            $table->dateTime('verified_at')->nullable();
            $table->string('status', 50)->default('pending'); // pending, verified, offset_applied
            $table->dateTime('created_at')->useCurrent();
            $table->foreign('student_id')->references('id')->on('students')->restrictOnDelete();
            $table->foreign('verified_by')->references('id')->on('staff')->restrictOnDelete();
        });

        Schema::create('payroll_items', function (Blueprint $table) {
            $table->id();
            $table->string('payslip_number', 50)->unique();
            $table->unsignedBigInteger('staff_id');
            $table->integer('pay_period_year');
            $table->integer('pay_period_month');
            $table->decimal('basic_salary', 12, 2);
            $table->decimal('gross_salary', 12, 2);
            $table->decimal('total_allowances', 12, 2)->default(0.00);
            $table->decimal('total_deductions', 12, 2)->default(0.00);
            $table->decimal('net_salary', 12, 2)->default(0.00);
            $table->tinyInteger('is_processed')->default(0);
            $table->unsignedBigInteger('processed_by')->nullable();
            $table->dateTime('processed_at')->nullable();
            $table->tinyInteger('is_approved')->default(0);
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->dateTime('approved_at')->nullable();
            $table->tinyInteger('is_disbursed')->default(0);
            $table->date('disbursement_date')->nullable();
            $table->string('eft_reference', 100)->nullable();
            $table->unsignedBigInteger('bank_transaction_id')->nullable();
            $table->dateTime('created_at')->useCurrent();
            $table->foreign('staff_id')->references('id')->on('staff')->restrictOnDelete();
            $table->foreign('processed_by')->references('id')->on('staff')->nullOnDelete();
            $table->foreign('approved_by')->references('id')->on('staff')->nullOnDelete();
            $table->index(['staff_id', 'pay_period_year', 'pay_period_month']);
        });

        Schema::create('statutory_deductions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('payroll_item_id');
            $table->unsignedBigInteger('staff_id');
            $table->string('deduction_type', 50); // paye, nssf_tier1, nssf_tier2, sha, ahl, pension, union_fee, sacco_deduction, loan_repayment, other
            $table->decimal('gross_salary_for_deduction', 12, 2);
            $table->decimal('deduction_rate', 5, 2)->nullable();
            $table->decimal('employer_contribution', 12, 2)->default(0.00);
            $table->decimal('employee_amount', 12, 2);
            $table->decimal('employer_amount', 12, 2)->default(0.00);
            $table->tinyInteger('is_remitted')->default(0);
            $table->date('remittance_date')->nullable();
            $table->string('remittance_reference', 100)->nullable();
            $table->string('kra_reference', 100)->nullable();
            $table->dateTime('created_at')->useCurrent();
            $table->foreign('payroll_item_id')->references('id')->on('payroll_items')->restrictOnDelete();
            $table->foreign('staff_id')->references('id')->on('staff')->restrictOnDelete();
        });

        Schema::create('bank_transactions', function (Blueprint $table) {
            $table->id();
            $table->date('transaction_date');
            $table->date('value_date');
            $table->string('description', 500);
            $table->decimal('debit_amount', 12, 2);
            $table->decimal('credit_amount', 12, 2);
            $table->decimal('balance', 12, 2);
            $table->string('bank_reference', 100)->nullable();
            $table->string('eft_reference', 100)->nullable();
            $table->tinyInteger('is_reconciled')->default(0);
            $table->dateTime('reconciled_at')->nullable();
            $table->string('source_file', 500)->nullable();
            $table->dateTime('created_at')->useCurrent();
        });

        Schema::table('payroll_items', function (Blueprint $table) {
            $table->foreign('bank_transaction_id')->references('id')->on('bank_transactions')->nullOnDelete();
        });

        Schema::create('procurement_requisitions', function (Blueprint $table) {
            $table->id();
            $table->string('requisition_number', 50)->unique();
            $table->unsignedBigInteger('requesting_department_id');
            $table->unsignedBigInteger('requested_by');
            $table->date('request_date');
            $table->text('justification');
            $table->decimal('estimated_cost', 12, 2);
            $table->string('budget_code', 50)->nullable();
            $table->date('delivery_required_by')->nullable();
            $table->string('status', 50)->default('draft'); // draft, submitted, hod_approved, finance_approved, ceo_approved, proc_in_progress, completed, rejected, cancelled
            $table->string('hod_approval_status', 50)->default('pending');
            $table->unsignedBigInteger('hod_approved_by')->nullable();
            $table->dateTime('hod_approved_at')->nullable();
            $table->string('finance_approval_status', 50)->default('pending');
            $table->unsignedBigInteger('finance_approved_by')->nullable();
            $table->dateTime('finance_approved_at')->nullable();
            $table->string('ceo_approval_status', 50)->default('pending');
            $table->unsignedBigInteger('ceo_approved_by')->nullable();
            $table->dateTime('ceo_approved_at')->nullable();
            $table->dateTime('created_at')->useCurrent();
            $table->foreign('requesting_department_id')->references('id')->on('departments')->restrictOnDelete();
            $table->foreign('requested_by')->references('id')->on('staff')->restrictOnDelete();
            $table->foreign('hod_approved_by')->references('id')->on('staff')->nullOnDelete();
            $table->foreign('finance_approved_by')->references('id')->on('staff')->nullOnDelete();
            $table->foreign('ceo_approved_by')->references('id')->on('staff')->nullOnDelete();
        });

        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->string('po_number', 50)->unique();
            $table->unsignedBigInteger('supplier_id');
            $table->unsignedBigInteger('requisition_id');
            $table->date('issue_date');
            $table->date('delivery_date')->nullable();
            $table->decimal('total_amount', 12, 2);
            $table->string('terms', 500)->nullable();
            $table->string('status', 50)->default('issued'); // issued, confirmed, partial_delivery, delivered, closed, cancelled
            $table->dateTime('created_at')->useCurrent();
            $table->foreign('supplier_id')->references('id')->on('suppliers')->restrictOnDelete();
            $table->foreign('requisition_id')->references('id')->on('procurement_requisitions')->restrictOnDelete();
        });

        Schema::create('goods_received_notes', function (Blueprint $table) {
            $table->id();
            $table->string('grn_number', 50)->unique();
            $table->unsignedBigInteger('purchase_order_id');
            $table->string('supplier_delivery_note', 100)->nullable();
            $table->date('received_date');
            $table->unsignedBigInteger('received_by');
            $table->string('inspection_status', 50)->default('pending'); // pending, passed, failed, partial
            $table->text('inspection_notes')->nullable();
            $table->text('shortages_or_damages')->nullable();
            $table->tinyInteger('is_complete')->default(0);
            $table->dateTime('created_at')->useCurrent();
            $table->foreign('purchase_order_id')->references('id')->on('purchase_orders')->restrictOnDelete();
            $table->foreign('received_by')->references('id')->on('staff')->restrictOnDelete();
        });

        Schema::create('accounts_payable', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number', 50)->unique();
            $table->unsignedBigInteger('supplier_id');
            $table->unsignedBigInteger('requisition_id')->nullable();
            $table->unsignedBigInteger('purchase_order_id')->nullable();
            $table->date('invoice_date');
            $table->date('due_date');
            $table->decimal('invoice_amount', 12, 2);
            $table->decimal('tax_amount', 12, 2)->default(0.00);
            $table->decimal('total_amount', 12, 2)->default(0.00);
            $table->decimal('amount_paid', 12, 2)->default(0.00);
            $table->decimal('balance', 12, 2)->default(0.00);
            $table->string('invoice_file_path', 500)->nullable();
            $table->string('three_way_match_status', 50)->default('pending'); // pending, approved, rejected, escalated
            $table->unsignedBigInteger('three_way_match_by')->nullable();
            $table->dateTime('three_way_match_at')->nullable();
            $table->string('finance_approval_status', 50)->default('pending');
            $table->unsignedBigInteger('finance_approved_by')->nullable();
            $table->dateTime('finance_approved_at')->nullable();
            $table->string('payment_status', 50)->default('unpaid'); // unpaid, partial, paid
            $table->date('payment_date')->nullable();
            $table->string('payment_reference', 100)->nullable();
            $table->string('payment_method', 50)->nullable();
            $table->tinyInteger('is_quickbooks_synced')->default(0);
            $table->string('quickbooks_sync_ref', 100)->nullable();
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->nullable()->useCurrentOnUpdate();
            $table->foreign('supplier_id')->references('id')->on('suppliers')->restrictOnDelete();
            $table->foreign('requisition_id')->references('id')->on('procurement_requisitions')->nullOnDelete();
            $table->foreign('purchase_order_id')->references('id')->on('purchase_orders')->nullOnDelete();
            $table->foreign('three_way_match_by')->references('id')->on('staff')->nullOnDelete();
            $table->foreign('finance_approved_by')->references('id')->on('staff')->nullOnDelete();
        });

        Schema::create('three_way_matches', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('accounts_payable_id');
            $table->unsignedBigInteger('purchase_order_id');
            $table->unsignedBigInteger('goods_received_note_id')->nullable();
            $table->string('match_result', 50); // match, price_variance, qty_variance, rejected
            $table->decimal('po_amount', 12, 2);
            $table->decimal('invoice_amount', 12, 2);
            $table->decimal('grn_amount', 12, 2);
            $table->decimal('variance_amount', 12, 2);
            $table->unsignedBigInteger('matched_by');
            $table->dateTime('matched_at');
            $table->tinyInteger('is_accepted')->default(0);
            $table->unsignedBigInteger('accepted_by')->nullable();
            $table->dateTime('accepted_at')->nullable();
            $table->foreign('accounts_payable_id')->references('id')->on('accounts_payable')->restrictOnDelete();
            $table->foreign('purchase_order_id')->references('id')->on('purchase_orders')->restrictOnDelete();
            $table->foreign('goods_received_note_id')->references('id')->on('goods_received_notes')->nullOnDelete();
            $table->foreign('matched_by')->references('id')->on('staff')->restrictOnDelete();
            $table->foreign('accepted_by')->references('id')->on('staff')->nullOnDelete();
        });

        Schema::create('donor_projects', function (Blueprint $table) {
            $table->id();
            $table->string('project_code', 50)->unique();
            $table->string('project_name', 300);
            $table->string('donor_name', 300);
            $table->string('donor_type', 50); // bilateral, multilateral, foundation, corporate, individual, government
            $table->decimal('total_grant_amount', 14, 2);
            $table->string('currency', 10)->default('KES');
            $table->decimal('disbursed_amount', 14, 2)->default(0.00);
            $table->string('disbursement_currency', 10)->default('USD');
            $table->decimal('exchange_rate_at_disbursement', 12, 4)->default(1.0000);
            $table->decimal('kes_equivalent', 14, 2)->default(0.00);
            $table->date('start_date');
            $table->date('end_date');
            $table->unsignedBigInteger('project_leader_id');
            $table->string('status', 50)->default('active'); // active, completed, suspended, closed
            $table->dateTime('created_at')->useCurrent();
            $table->foreign('project_leader_id')->references('id')->on('staff')->restrictOnDelete();
        });

        Schema::create('donor_disbursements', function (Blueprint $table) {
            $table->id();
            $table->string('disbursement_number', 50)->unique();
            $table->unsignedBigInteger('donor_project_id');
            $table->decimal('amount_received', 14, 2);
            $table->string('currency_received', 10)->default('USD');
            $table->decimal('exchange_rate', 12, 4);
            $table->decimal('kes_amount', 14, 2);
            $table->date('receipt_date');
            $table->string('bank_reference', 100)->nullable();
            $table->string('purpose', 500)->nullable();
            $table->unsignedBigInteger('account_ledger_id')->nullable();
            $table->dateTime('created_at')->useCurrent();
            $table->foreign('donor_project_id')->references('id')->on('donor_projects')->restrictOnDelete();
            $table->foreign('account_ledger_id')->references('id')->on('account_ledger')->nullOnDelete();
        });

        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->string('asset_number', 50)->unique();
            $table->string('asset_name', 300);
            $table->string('asset_category', 50); // furniture, equipment, vehicle, building, it_hardware, other
            $table->string('serial_number', 100)->nullable();
            $table->string('description', 500)->nullable();
            $table->date('acquisition_date');
            $table->decimal('acquisition_cost', 12, 2);
            $table->unsignedBigInteger('supplier_id')->nullable();
            $table->unsignedBigInteger('purchase_order_id')->nullable();
            $table->integer('useful_life_years')->default(0);
            $table->string('depreciation_method', 50)->default('straight_line');
            $table->decimal('salvage_value', 12, 2)->default(0.00);
            $table->decimal('current_value', 12, 2)->default(0.00);
            $table->decimal('depreciation_per_year', 12, 2)->default(0.00);
            $table->decimal('accumulated_depreciation', 12, 2)->default(0.00);
            $table->string('condition', 50)->default('new'); // new, good, fair, poor, disposed
            $table->date('disposed_date')->nullable();
            $table->decimal('disposed_value', 12, 2)->nullable();
            $table->string('disposed_reason', 500)->nullable();
            $table->date('warranty_expiry_date')->nullable();
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->nullable()->useCurrentOnUpdate();
            $table->foreign('supplier_id')->references('id')->on('suppliers')->nullOnDelete();
            $table->foreign('purchase_order_id')->references('id')->on('purchase_orders')->nullOnDelete();
        });

        Schema::create('asset_assignments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('asset_id');
            $table->string('assigned_to_type', 50); // staff, department, student
            $table->unsignedBigInteger('assigned_to_id');
            $table->date('assignment_date');
            $table->date('return_date')->nullable();
            $table->tinyInteger('is_returned')->default(0);
            $table->string('condition_on_assignment', 50)->default('new');
            $table->string('condition_on_return', 50)->nullable();
            $table->unsignedBigInteger('assigned_by');
            $table->unsignedBigInteger('returned_to')->nullable();
            $table->text('notes')->nullable();
            $table->dateTime('created_at')->useCurrent();
            $table->foreign('asset_id')->references('id')->on('assets')->restrictOnDelete();
            $table->foreign('assigned_by')->references('id')->on('staff')->restrictOnDelete();
            $table->foreign('returned_to')->references('id')->on('staff')->nullOnDelete();
        });

        Schema::create('inventory_items', function (Blueprint $table) {
            $table->id();
            $table->string('item_code', 50)->unique();
            $table->string('item_name', 300);
            $table->string('category', 100)->nullable();
            $table->string('unit_of_measure', 30)->default('unit');
            $table->integer('current_stock')->default(0);
            $table->integer('minimum_stock')->default(0);
            $table->integer('maximum_stock')->default(0);
            $table->integer('reorder_level')->default(0);
            $table->decimal('unit_cost', 12, 2)->default(0.00);
            $table->unsignedBigInteger('supplier_id')->nullable();
            $table->string('store_location', 100)->nullable();
            $table->tinyInteger('is_active')->default(1);
            $table->dateTime('created_at')->useCurrent();
            $table->foreign('supplier_id')->references('id')->on('suppliers')->nullOnDelete();
        });

        Schema::create('inventory_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('inventory_item_id');
            $table->string('transaction_type', 50); // purchase, issue, adjustment, stock_take, return
            $table->integer('quantity');
            $table->decimal('unit_cost', 12, 2);
            $table->decimal('total_cost', 12, 2)->default(0.00);
            $table->string('reference_table', 50)->nullable();
            $table->string('reference_id', 50)->nullable();
            $table->string('from_location', 100)->nullable();
            $table->string('to_location', 100)->nullable();
            $table->unsignedBigInteger('department_id')->nullable();
            $table->unsignedBigInteger('recorded_by');
            $table->date('transaction_date');
            $table->text('notes')->nullable();
            $table->dateTime('created_at')->useCurrent();
            $table->foreign('inventory_item_id')->references('id')->on('inventory_items')->restrictOnDelete();
            $table->foreign('department_id')->references('id')->on('departments')->nullOnDelete();
            $table->foreign('recorded_by')->references('id')->on('staff')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_transactions');
        Schema::dropIfExists('inventory_items');
        Schema::dropIfExists('asset_assignments');
        Schema::dropIfExists('assets');
        Schema::dropIfExists('donor_disbursements');
        Schema::dropIfExists('donor_projects');
        Schema::dropIfExists('three_way_matches');
        Schema::dropIfExists('accounts_payable');
        Schema::dropIfExists('goods_received_notes');
        Schema::dropIfExists('purchase_orders');
        Schema::dropIfExists('procurement_requisitions');
        Schema::dropIfExists('statutory_deductions');

        Schema::table('payroll_items', function (Blueprint $table) {
            $table->dropForeign(['bank_transaction_id']);
        });

        Schema::dropIfExists('bank_transactions');
        Schema::dropIfExists('payroll_items');
        Schema::dropIfExists('work_study_ledger');
        Schema::dropIfExists('account_ledger');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('student_accounts');
        Schema::dropIfExists('fee_structures');
        Schema::dropIfExists('chart_of_accounts');
        Schema::dropIfExists('suppliers');
    }
};
