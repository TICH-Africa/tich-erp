<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('chart_of_accounts', 'currency')) {
            Schema::table('chart_of_accounts', function (Blueprint $table) {
                $table->string('currency', 10)->default('KES')->after('account_type');
            });
        }

        DB::table('chart_of_accounts')->update(['currency' => 'KES']);

        if (Schema::hasColumn('chart_of_accounts', 'account_category')) {
            Schema::table('chart_of_accounts', function (Blueprint $table) {
                $table->dropColumn('account_category');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('chart_of_accounts', 'account_category')) {
            Schema::table('chart_of_accounts', function (Blueprint $table) {
                $table->string('account_category', 100)->default('General')->after('account_type');
            });
        }

        DB::table('chart_of_accounts')->update(['account_category' => 'General']);

        if (Schema::hasColumn('chart_of_accounts', 'currency')) {
            Schema::table('chart_of_accounts', function (Blueprint $table) {
                $table->dropColumn('currency');
            });
        }
    }
};
