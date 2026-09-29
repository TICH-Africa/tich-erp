<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            if (! Schema::hasColumn('students', 'current_year')) {
                $table->tinyInteger('current_year')->nullable()->after('cohort_intake')->comment('Current academic year: 1, 2, 3, 4');
            }
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            if (Schema::hasColumn('students', 'current_year')) {
                $table->dropColumn('current_year');
            }
        });
    }
};