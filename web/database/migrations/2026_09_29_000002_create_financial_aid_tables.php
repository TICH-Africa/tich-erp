<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('financial_aid_opportunities', function (Blueprint $table) {
            $table->id();
            $table->string('title', 300);
            $table->string('slug', 300)->unique();
            $table->text('description');
            $table->text('eligibility_criteria')->nullable();
            $table->text('application_process')->nullable();
            $table->decimal('amount', 12, 2)->nullable(); // Amount available for this opportunity
            $table->enum('funding_type', ['scholarship', 'grant', 'loan', 'work_study'])->default('scholarship');
            $table->date('application_open_date')->nullable();
            $table->date('application_deadline')->nullable();
            $table->enum('status', ['draft', 'published', 'closed', 'archived'])->default('draft');
            $table->dateTime('published_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'published_at']);
            $table->index('application_deadline');
        });

        Schema::create('financial_aid_applications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('financial_aid_opportunity_id');
            $table->unsignedBigInteger('student_id')->nullable(); // User ID or Student ID
            $table->string('student_name', 300);
            $table->string('student_email', 255);
            $table->string('student_phone', 30)->nullable();
            $table->string('student_number', 50)->nullable(); // Student ID number
            $table->string('program_applied', 300)->nullable();
            $table->text('personal_statement')->nullable();
            $table->text('financial_need_statement')->nullable();
            $table->json('supporting_documents')->nullable(); // Array of document paths
            $table->enum('status', ['pending', 'under_review', 'approved', 'rejected', 'allocated'])->default('pending');
            $table->text('admin_notes')->nullable();
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->dateTime('reviewed_at')->nullable();
            $table->decimal('approved_amount', 12, 2)->nullable();
            $table->enum('allocation_status', ['pending', 'allocated', 'partial'])->default('pending');
            $table->unsignedBigInteger('allocated_by')->nullable();
            $table->dateTime('allocated_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('financial_aid_opportunity_id')->references('id')->on('financial_aid_opportunities')->onDelete('cascade');
            $table->foreign('student_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('reviewed_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('allocated_by')->references('id')->on('users')->onDelete('set null');
            $table->index(['financial_aid_opportunity_id', 'status']);
            $table->index('student_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_aid_applications');
        Schema::dropIfExists('financial_aid_opportunities');
    }
};