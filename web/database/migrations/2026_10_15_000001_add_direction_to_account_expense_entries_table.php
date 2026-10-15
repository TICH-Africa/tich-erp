<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('account_expense_entries') || Schema::hasColumn('account_expense_entries', 'direction')) {
            return;
        }

        Schema::table('account_expense_entries', function (Blueprint $table) {
            $table->string('direction', 10)->default('increase')->after('amount');
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('account_expense_entries') && Schema::hasColumn('account_expense_entries', 'direction')) {
            Schema::table('account_expense_entries', function (Blueprint $table) {
                $table->dropColumn('direction');
            });
        }
    }
};
