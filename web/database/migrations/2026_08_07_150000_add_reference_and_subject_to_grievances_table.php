<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('grievances', function (Blueprint $table) {
            if (! Schema::hasColumn('grievances', 'reference_number')) {
                $table->string('reference_number', 30)->nullable()->unique()->after('id');
            }

            if (! Schema::hasColumn('grievances', 'subject')) {
                $table->string('subject', 300)->nullable()->after('grievance_type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('grievances', function (Blueprint $table) {
            if (Schema::hasColumn('grievances', 'subject')) {
                $table->dropColumn('subject');
            }

            if (Schema::hasColumn('grievances', 'reference_number')) {
                $table->dropColumn('reference_number');
            }
        });
    }
};
