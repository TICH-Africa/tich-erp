<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assignments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('unit_id');
            $table->unsignedBigInteger('unit_allocation_id');
            $table->unsignedBigInteger('semester_id');
            $table->unsignedBigInteger('created_by');
            $table->string('title', 300);
            $table->text('description')->nullable();
            $table->text('instructions')->nullable();
            $table->string('attachment_path')->nullable();
            $table->string('attachment_filename')->nullable();
            $table->string('mime_type')->nullable();
            $table->integer('file_size')->nullable();
            $table->decimal('max_score', 6, 2)->default(100);
            $table->dateTime('due_date')->nullable();
            $table->boolean('allow_late_submission')->default(false);
            $table->string('status', 50)->default('draft'); // draft, published, closed
            $table->dateTime('published_at')->nullable();
            $table->dateTime('available_from')->nullable();
            $table->text('submission_instructions')->nullable();
            $table->integer('display_order')->default(0);
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->nullable()->useCurrentOnUpdate();
            $table->foreign('unit_id')->references('id')->on('units')->restrictOnDelete();
            $table->foreign('unit_allocation_id')->references('id')->on('unit_allocations')->restrictOnDelete();
            $table->foreign('semester_id')->references('id')->on('semesters')->restrictOnDelete();
            $table->foreign('created_by')->references('id')->on('staff')->restrictOnDelete();
            $table->index(['unit_allocation_id', 'status', 'due_date']);
        });

        Schema::create('assignment_submissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('assignment_id');
            $table->unsignedBigInteger('student_id');
            $table->text('submission_text')->nullable();
            $table->string('attachment_path')->nullable();
            $table->string('attachment_filename')->nullable();
            $table->string('mime_type')->nullable();
            $table->integer('file_size')->nullable();
            $table->dateTime('submitted_at')->nullable();
            $table->decimal('grade', 6, 2)->nullable();
            $table->text('feedback')->nullable();
            $table->unsignedBigInteger('graded_by')->nullable();
            $table->dateTime('graded_at')->nullable();
            $table->string('status', 50)->default('pending'); // pending, submitted, late, graded, returned
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->nullable()->useCurrentOnUpdate();
            $table->unique(['assignment_id', 'student_id'], 'assignment_sub_unique');
            $table->foreign('assignment_id')->references('id')->on('assignments')->cascadeOnDelete();
            $table->foreign('student_id')->references('id')->on('students')->restrictOnDelete();
            $table->foreign('graded_by')->references('id')->on('staff')->nullOnDelete();
            $table->index(['student_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assignment_submissions');
        Schema::dropIfExists('assignments');
    }
};
