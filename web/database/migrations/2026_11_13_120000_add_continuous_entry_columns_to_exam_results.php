<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exam_results', function (Blueprint $table) {
            // A lecturer can stand by a figure the system never captured (paper CATs,
            // attendance taken by hand). What they type is what the grade follows.
            $table->decimal('cat_avg_entry', 5, 2)->nullable()->after('practical_total');
            $table->decimal('practical_avg_entry', 5, 2)->nullable()->after('cat_avg_entry');
            $table->decimal('attendance_pct_entry', 5, 2)->nullable()->after('practical_avg_entry');
        });
    }

    public function down(): void
    {
        Schema::table('exam_results', function (Blueprint $table) {
            $table->dropColumn(['cat_avg_entry', 'practical_avg_entry', 'attendance_pct_entry']);
        });
    }
};
