<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fee_structures', function (Blueprint $table) {
            if (Schema::hasIndex('fee_structures', 'fee_struct_program_year_unique')) {
                $table->dropUnique('fee_struct_program_year_unique');
            }
        });

        Schema::table('fee_structures', function (Blueprint $table) {
            if (! Schema::hasIndex('fee_structures', 'fee_struct_program_year_tier_unique')) {
                $table->unique(
                    ['program_id', 'academic_year_id', 'program_tier'],
                    'fee_struct_program_year_tier_unique'
                );
            }
        });
    }

    public function down(): void
    {
        Schema::table('fee_structures', function (Blueprint $table) {
            if (Schema::hasIndex('fee_structures', 'fee_struct_program_year_tier_unique')) {
                $table->dropUnique('fee_struct_program_year_tier_unique');
            }
        });

        Schema::table('fee_structures', function (Blueprint $table) {
            if (! Schema::hasIndex('fee_structures', 'fee_struct_program_year_unique')) {
                $table->unique(['program_id', 'academic_year_id'], 'fee_struct_program_year_unique');
            }
        });
    }
};
