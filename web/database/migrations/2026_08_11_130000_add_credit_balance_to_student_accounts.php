<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('student_accounts') && ! Schema::hasColumn('student_accounts', 'credit_balance')) {
            Schema::table('student_accounts', function (Blueprint $table) {
                $table->decimal('credit_balance', 12, 2)->default(0.00)->after('sponsor_amount');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('student_accounts') && Schema::hasColumn('student_accounts', 'credit_balance')) {
            Schema::table('student_accounts', function (Blueprint $table) {
                $table->dropColumn('credit_balance');
            });
        }
    }
};
