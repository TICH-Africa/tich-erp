<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('financial_aid_applications', function (Blueprint $table) {
            if (! Schema::hasColumn('financial_aid_applications', 'approved_at')) {
                $table->dateTime('approved_at')->nullable()->after('reviewed_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('financial_aid_applications', function (Blueprint $table) {
            if (Schema::hasColumn('financial_aid_applications', 'approved_at')) {
                $table->dropColumn('approved_at');
            }
        });
    }
};