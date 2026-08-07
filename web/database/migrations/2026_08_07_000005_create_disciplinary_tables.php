<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('disciplinary_cases', function (Blueprint $table) {
            $table->id();
            $table->string('case_number')->unique();
            $table->foreignId('staff_id')->constrained('staff')->cascadeOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('staff')->nullOnDelete();
            $table->date('incident_date');
            $table->text('incident_description');
            $table->text('investigation_notes')->nullable();
            $table->text('witness_information')->nullable();
            $table->date('hearing_date')->nullable();
            $table->text('committee_members')->nullable();
            $table->text('decision')->nullable();
            $table->enum('action_type', ['warning', 'suspension', 'termination', 'appeal', 'other'])->nullable();
            $table->text('action_details')->nullable();
            $table->date('action_start_date')->nullable();
            $table->date('action_end_date')->nullable();
            $table->enum('status', ['open', 'under_investigation', 'hearing_scheduled', 'decided', 'appealed', 'closed'])->default('open');
            $table->text('hr_comments')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['staff_id', 'status']);
            $table->index('case_number');
        });

        Schema::create('disciplinary_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('disciplinary_case_id')->constrained('disciplinary_cases')->cascadeOnDelete();
            $table->string('document_name');
            $table->string('document_path');
            $table->string('mime_type')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('disciplinary_documents');
        Schema::dropIfExists('disciplinary_cases');
    }
};
