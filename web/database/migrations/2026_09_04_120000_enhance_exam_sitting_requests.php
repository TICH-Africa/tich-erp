<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('special_exam_requests', function (Blueprint $table) {
            $table->dropForeign(['exam_result_id']);
        });

        Schema::table('special_exam_requests', function (Blueprint $table) {
            $table->unsignedBigInteger('exam_result_id')->nullable()->change();
            $table->unsignedBigInteger('unit_id')->nullable()->after('exam_result_id');
            $table->unsignedBigInteger('semester_id')->nullable()->after('unit_id');
            $table->text('student_notes')->nullable()->after('reason');
            $table->text('reviewed_notes')->nullable()->after('reviewed_at');

            $table->foreign('exam_result_id')->references('id')->on('exam_results')->restrictOnDelete();
            $table->foreign('unit_id')->references('id')->on('units')->nullOnDelete();
            $table->foreign('semester_id')->references('id')->on('semesters')->nullOnDelete();
        });

        Schema::table('supplementary_requests', function (Blueprint $table) {
            $table->dropForeign(['exam_result_id']);
        });

        Schema::table('supplementary_requests', function (Blueprint $table) {
            $table->unsignedBigInteger('exam_result_id')->nullable()->change();
            $table->unsignedBigInteger('unit_id')->nullable()->after('exam_result_id');
            $table->unsignedBigInteger('semester_id')->nullable()->after('unit_id');
            $table->text('student_notes')->nullable()->after('application_status');
            $table->unsignedBigInteger('reviewed_by')->nullable()->after('student_notes');
            $table->dateTime('reviewed_at')->nullable()->after('reviewed_by');
            $table->text('reviewed_notes')->nullable()->after('reviewed_at');

            $table->foreign('exam_result_id')->references('id')->on('exam_results')->restrictOnDelete();
            $table->foreign('unit_id')->references('id')->on('units')->nullOnDelete();
            $table->foreign('semester_id')->references('id')->on('semesters')->nullOnDelete();
            $table->foreign('reviewed_by')->references('id')->on('staff')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('supplementary_requests', function (Blueprint $table) {
            $table->dropForeign(['exam_result_id']);
            $table->dropForeign(['unit_id']);
            $table->dropForeign(['semester_id']);
            $table->dropForeign(['reviewed_by']);
            $table->dropColumn(['unit_id', 'semester_id', 'student_notes', 'reviewed_by', 'reviewed_at', 'reviewed_notes']);
            $table->unsignedBigInteger('exam_result_id')->nullable(false)->change();
            $table->foreign('exam_result_id')->references('id')->on('exam_results')->restrictOnDelete();
        });

        Schema::table('special_exam_requests', function (Blueprint $table) {
            $table->dropForeign(['exam_result_id']);
            $table->dropForeign(['unit_id']);
            $table->dropForeign(['semester_id']);
            $table->dropColumn(['unit_id', 'semester_id', 'student_notes', 'reviewed_notes']);
            $table->unsignedBigInteger('exam_result_id')->nullable(false)->change();
            $table->foreign('exam_result_id')->references('id')->on('exam_results')->restrictOnDelete();
        });
    }
};
