<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A unit can set the mark its students need to pass, next to the weights that build the final
 * score. Left empty the unit grades on the mark its programme carries, so nothing moves by accident.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('units') && ! Schema::hasColumn('units', 'pass_mark')) {
            Schema::table('units', function (Blueprint $table) {
                $table->decimal('pass_mark', 5, 2)->nullable()->after('assessment_weight_exam_pct');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('units') && Schema::hasColumn('units', 'pass_mark')) {
            Schema::table('units', function (Blueprint $table) {
                $table->dropColumn('pass_mark');
            });
        }
    }
};
