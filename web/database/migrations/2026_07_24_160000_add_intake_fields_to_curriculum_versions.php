<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('curriculum_versions', function (Blueprint $table) {
            if (! Schema::hasColumn('curriculum_versions', 'intake_year')) {
                $table->unsignedSmallInteger('intake_year')->nullable()->after('academic_year_id');
            }
            if (! Schema::hasColumn('curriculum_versions', 'intake_month')) {
                $table->unsignedTinyInteger('intake_month')->nullable()->after('intake_year');
            }
        });

        Schema::table('curriculum_versions', function (Blueprint $table) {
            $table->unique(['program_id', 'intake_year', 'intake_month'], 'cv_program_intake_unique');
        });
    }

    public function down(): void
    {
        Schema::table('curriculum_versions', function (Blueprint $table) {
            $table->dropUnique('cv_program_intake_unique');
            foreach (['intake_month', 'intake_year'] as $column) {
                if (Schema::hasColumn('curriculum_versions', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
