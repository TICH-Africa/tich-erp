<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->string('supplier_category', 50)->nullable()->after('is_active'); // goods, services, works
            $table->string('sub_category', 100)->nullable()->after('supplier_category');
            $table->string('risk_rating', 20)->default('medium'); // low, medium, high
            $table->string('compliance_status', 50)->default('pending'); // pending, approved, rejected, under_review
            $table->decimal('performance_score', 5, 2)->default(50.00)->after('compliance_status');
            $table->string('registration_number', 100)->nullable()->after('supplier_name');
            $table->string('pin_certificate_path', 500)->nullable()->after('compliance_doc_path');
            $table->string('cr12_path', 500)->nullable()->after('pin_certificate_path');
            $table->string('audited_financial_statements_path', 500)->nullable()->after('cr12_path');
            $table->json('past_contracts')->nullable()->after('audited_financial_statements_path');
            $table->json('client_references')->nullable()->after('past_contracts');
            $table->integer('staff_count')->nullable()->after('client_references');
            $table->json('certifications')->nullable()->after('staff_count'); // ISO, etc.
            $table->string('blacklist_status', 50)->default('active'); // active, blacklisted, under_review
            $table->text('blacklist_reason')->nullable()->after('blacklist_status');
            $table->dateTime('blacklisted_at')->nullable()->after('blacklist_reason');
            $table->unsignedBigInteger('blacklisted_by')->nullable()->after('blacklisted_at');
            $table->dateTime('performance_last_updated_at')->nullable()->after('blacklisted_by');
            $table->unsignedBigInteger('performance_updated_by')->nullable()->after('performance_last_updated_at');
            $table->text('notes')->nullable()->after('performance_updated_by');
            $table->dateTime('created_at')->nullable()->useCurrent()->change();
            $table->foreign('blacklisted_by')->references('id')->on('staff')->nullOnDelete();
            $table->foreign('performance_updated_by')->references('id')->on('staff')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropForeign(['blacklisted_by']);
            $table->dropForeign(['performance_updated_by']);
            $table->dropColumn([
                'supplier_category',
                'sub_category',
                'risk_rating',
                'compliance_status',
                'performance_score',
                'registration_number',
                'pin_certificate_path',
                'cr12_path',
                'audited_financial_statements_path',
                'past_contracts',
                'client_references',
                'staff_count',
                'certifications',
                'blacklist_status',
                'blacklist_reason',
                'blacklisted_at',
                'blacklisted_by',
                'performance_last_updated_at',
                'performance_updated_by',
                'notes',
            ]);
            $table->dateTime('created_at')->nullable()->change();
        });
    }
};
