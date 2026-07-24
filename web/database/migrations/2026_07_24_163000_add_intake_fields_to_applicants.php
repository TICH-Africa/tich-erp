<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applicants', function (Blueprint $table) {
            if (! Schema::hasColumn('applicants', 'intake_year')) {
                $table->unsignedSmallInteger('intake_year')->nullable()->after('program_id');
            }
            if (! Schema::hasColumn('applicants', 'intake_month')) {
                $table->unsignedTinyInteger('intake_month')->nullable()->after('intake_year');
            }
        });
    }

    public function down(): void
    {
        Schema::table('applicants', function (Blueprint $table) {
            foreach (['intake_month', 'intake_year'] as $column) {
                if (Schema::hasColumn('applicants', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
