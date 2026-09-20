<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('academic_records', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('program_id');
            $table->unsignedBigInteger('academic_year_id')->nullable();
            $table->unsignedBigInteger('semester_id')->nullable();
            $table->date('enrollment_date');
            $table->date('completion_date')->nullable();
            $table->string('status', 50)->default('active');
            $table->decimal('gpa', 4, 2)->nullable();
            $table->integer('units_registered')->default(0);
            $table->integer('units_completed')->default(0);
            $table->string('entry_pathway', 100)->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('student_id')->references('id')->on('students')->restrictOnDelete();
            $table->foreign('program_id')->references('id')->on('academic_programs')->restrictOnDelete();
            $table->foreign('academic_year_id')->references('id')->on('academic_years')->nullOnDelete();
            $table->foreign('semester_id')->references('id')->on('semesters')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('staff')->nullOnDelete();

            $table->index(['student_id', 'status']);
            $table->index(['program_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academic_records');
    }
};
