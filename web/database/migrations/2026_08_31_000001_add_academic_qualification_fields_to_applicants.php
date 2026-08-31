<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('applicants')) {
            return;
        }

        Schema::table('applicants', function (Blueprint $table) {
            if (! Schema::hasColumn('applicants', 'kcse_grade')) {
                $table->string('kcse_grade', 20)->nullable()->after('entry_qualification');
            }
            if (! Schema::hasColumn('applicants', 'kcse_year')) {
                $table->unsignedSmallInteger('kcse_year')->nullable()->after('kcse_grade');
            }
            if (! Schema::hasColumn('applicants', 'previous_institution')) {
                $table->string('previous_institution', 200)->nullable()->after('kcse_year');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('applicants')) {
            return;
        }

        Schema::table('applicants', function (Blueprint $table) {
            foreach (['kcse_grade', 'kcse_year', 'previous_institution'] as $column) {
                if (Schema::hasColumn('applicants', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
