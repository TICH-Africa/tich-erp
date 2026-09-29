<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            if (! Schema::hasColumn('suppliers', 'payment_method')) {
                $table->string('payment_method', 20)->default('bank')->after('bank_code');
            }
            if (! Schema::hasColumn('suppliers', 'paybill_number')) {
                $table->string('paybill_number', 20)->nullable()->after('payment_method');
            }
            if (! Schema::hasColumn('suppliers', 'paybill_account_number')) {
                $table->string('paybill_account_number', 50)->nullable()->after('paybill_number');
            }
            if (! Schema::hasColumn('suppliers', 'till_number')) {
                $table->string('till_number', 20)->nullable()->after('paybill_account_number');
            }
            if (! Schema::hasColumn('suppliers', 'mobile_number')) {
                $table->string('mobile_number', 20)->nullable()->after('till_number');
            }
        });
    }

    public function down(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $columnsToDrop = array_filter([
                Schema::hasColumn('suppliers', 'payment_method') ? 'payment_method' : null,
                Schema::hasColumn('suppliers', 'paybill_number') ? 'paybill_number' : null,
                Schema::hasColumn('suppliers', 'paybill_account_number') ? 'paybill_account_number' : null,
                Schema::hasColumn('suppliers', 'till_number') ? 'till_number' : null,
                Schema::hasColumn('suppliers', 'mobile_number') ? 'mobile_number' : null,
            ]);
            if (! empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};