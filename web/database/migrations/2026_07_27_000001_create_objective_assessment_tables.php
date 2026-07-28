<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('objective_assessments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('unit_allocation_id');
            $table->unsignedBigInteger('unit_id');
            $table->unsignedBigInteger('semester_id');
            $table->string('name', 200);
            $table->string('assessment_type', 50)->default('mcq'); // mcq, true_false, matching
            $table->decimal('max_score', 5, 2)->default(100);
            $table->unsignedBigInteger('created_by');
            $table->string('status', 50)->default('draft'); // draft, ready, graded
            $table->dateTime('auto_graded_at')->nullable();
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->nullable()->useCurrentOnUpdate();
            $table->foreign('unit_allocation_id')->references('id')->on('unit_allocations')->restrictOnDelete();
            $table->foreign('unit_id')->references('id')->on('units')->restrictOnDelete();
            $table->foreign('semester_id')->references('id')->on('semesters')->restrictOnDelete();
            $table->foreign('created_by')->references('id')->on('staff')->restrictOnDelete();
        });

        Schema::create('objective_questions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('objective_assessment_id');
            $table->unsignedSmallInteger('sort_order')->default(1);
            $table->text('question_text');
            $table->string('question_type', 50)->default('mcq'); // mcq, true_false, matching
            $table->json('options')->nullable();
            $table->string('correct_answer', 500);
            $table->decimal('points', 5, 2)->default(1);
            $table->foreign('objective_assessment_id')->references('id')->on('objective_assessments')->cascadeOnDelete();
        });

        Schema::create('objective_submissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('objective_assessment_id');
            $table->unsignedBigInteger('student_id');
            $table->json('responses')->nullable();
            $table->decimal('score_obtained', 5, 2)->nullable();
            $table->decimal('percentage_score', 5, 2)->nullable();
            $table->unsignedSmallInteger('correct_count')->default(0);
            $table->unsignedSmallInteger('question_count')->default(0);
            $table->dateTime('auto_graded_at')->nullable();
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->nullable()->useCurrentOnUpdate();
            $table->unique(['objective_assessment_id', 'student_id'], 'obj_sub_unique');
            $table->foreign('objective_assessment_id')->references('id')->on('objective_assessments')->cascadeOnDelete();
            $table->foreign('student_id')->references('id')->on('students')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('objective_submissions');
        Schema::dropIfExists('objective_questions');
        Schema::dropIfExists('objective_assessments');
    }
};
