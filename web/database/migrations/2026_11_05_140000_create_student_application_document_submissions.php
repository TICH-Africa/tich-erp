<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('student_application_document_submissions')) {
            return;
        }

        Schema::create('student_application_document_submissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('applicant_id');
            $table->unsignedBigInteger('application_document_id')->nullable();
            $table->string('document_type', 50);
            $table->string('file_path', 500);
            $table->string('original_filename', 255);
            $table->string('mime_type', 100);
            $table->string('status', 30)->default('pending');
            $table->text('student_notes')->nullable();
            $table->text('reviewer_notes')->nullable();
            $table->unsignedBigInteger('uploaded_by_user_id');
            $table->unsignedBigInteger('reviewed_by_user_id')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->foreign('student_id', 'stu_app_doc_sub_student_id_fk')->references('id')->on('students')->cascadeOnDelete();
            $table->foreign('applicant_id', 'stu_app_doc_sub_applicant_id_fk')->references('id')->on('applicants')->cascadeOnDelete();
            $table->foreign('application_document_id', 'stu_app_doc_sub_app_doc_id_fk')->references('id')->on('application_documents')->nullOnDelete();
            $table->foreign('uploaded_by_user_id', 'stu_app_doc_sub_uploader_id_fk')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('reviewed_by_user_id', 'stu_app_doc_sub_reviewer_id_fk')->references('id')->on('users')->nullOnDelete();
            $table->index(['status', 'created_at']);
            $table->index(['student_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_application_document_submissions');
    }
};
