<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('policy_acknowledgements', function (Blueprint $table) {
            if (! Schema::hasColumn('policy_acknowledgements', 'acknowledged_by')) {
                $table->string('acknowledged_by', 200)->nullable()->after('acknowledged_at');
            }
            if (! Schema::hasColumn('policy_acknowledgements', 'employee_number')) {
                $table->string('employee_number', 50)->nullable()->after('acknowledged_by');
            }
            if (! Schema::hasColumn('policy_acknowledgements', 'signature')) {
                $table->text('signature')->nullable()->after('employee_number');
            }
        });
    }

    public function down(): void
    {
        Schema::table('policy_acknowledgements', function (Blueprint $table) {
            if (Schema::hasColumn('policy_acknowledgements', 'signature')) {
                $table->dropColumn('signature');
            }
            if (Schema::hasColumn('policy_acknowledgements', 'employee_number')) {
                $table->dropColumn('employee_number');
            }
            if (Schema::hasColumn('policy_acknowledgements', 'acknowledged_by')) {
                $table->dropColumn('acknowledged_by');
            }
        });
    }
};
