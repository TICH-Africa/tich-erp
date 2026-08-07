<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grievances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_id')->constrained('staff')->cascadeOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('staff')->nullOnDelete();
            $table->string('grievance_type')->nullable();
            $table->text('description');
            $table->date('incident_date')->nullable();
            $table->text('resolution_notes')->nullable();
            $table->enum('status', ['open', 'under_review', 'resolved', 'closed'])->default('open');
            $table->date('resolved_at')->nullable();
            $table->text('hr_comments')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['staff_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grievances');
    }
};
