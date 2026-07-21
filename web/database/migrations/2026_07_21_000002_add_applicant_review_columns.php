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
            if (! Schema::hasColumn('applicants', 'handling_department_id')) {
                $table->unsignedBigInteger('handling_department_id')->nullable()->after('program_id');
                $table->foreign('handling_department_id')->references('id')->on('departments')->nullOnDelete();
            }

            if (! Schema::hasColumn('applicants', 'review_notes')) {
                $table->text('review_notes')->nullable()->after('academic_review_status');
            }

            if (! Schema::hasColumn('applicants', 'rejection_reason')) {
                $table->string('rejection_reason', 500)->nullable()->after('review_notes');
            }

            if (! Schema::hasColumn('applicants', 'reviewed_at')) {
                $table->dateTime('reviewed_at')->nullable()->after('rejection_reason');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('applicants')) {
            return;
        }

        Schema::table('applicants', function (Blueprint $table) {
            if (Schema::hasColumn('applicants', 'handling_department_id')) {
                $table->dropForeign(['handling_department_id']);
                $table->dropColumn('handling_department_id');
            }

            foreach (['review_notes', 'rejection_reason', 'reviewed_at'] as $column) {
                if (Schema::hasColumn('applicants', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
