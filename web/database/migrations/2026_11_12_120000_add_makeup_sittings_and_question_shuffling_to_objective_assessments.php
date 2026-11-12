<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('objective_assessments', function (Blueprint $table) {
            $table->boolean('shuffle_questions')->default(false)->after('show_results_immediately');
        });

        Schema::table('objective_submissions', function (Blueprint $table) {
            $table->dateTime('open_from')->nullable()->after('attempt_number');
            $table->dateTime('open_until')->nullable()->after('open_from');
            $table->string('shuffle_seed', 32)->nullable()->after('open_until');
        });
    }

    public function down(): void
    {
        Schema::table('objective_assessments', function (Blueprint $table) {
            $table->dropColumn('shuffle_questions');
        });

        Schema::table('objective_submissions', function (Blueprint $table) {
            $table->dropColumn(['open_from', 'open_until', 'shuffle_seed']);
        });
    }
};
