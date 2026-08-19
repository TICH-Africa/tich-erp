<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admin_budget_requests', function (Blueprint $table) {
            if (! Schema::hasColumn('admin_budget_requests', 'disbursed_at')) {
                $table->dateTime('disbursed_at')->nullable()->after('executive_approved_at');
            }
            if (! Schema::hasColumn('admin_budget_requests', 'disbursed_by')) {
                $table->unsignedBigInteger('disbursed_by')->nullable()->after('disbursed_at');
            }
            if (! Schema::hasColumn('admin_budget_requests', 'receipt_number')) {
                $table->string('receipt_number', 100)->nullable()->after('disbursed_by');
            }
        });
    }

    public function down(): void
    {
        Schema::table('admin_budget_requests', function (Blueprint $table) {
            $table->dropColumn(['disbursed_at', 'disbursed_by', 'receipt_number']);
        });
    }
};
