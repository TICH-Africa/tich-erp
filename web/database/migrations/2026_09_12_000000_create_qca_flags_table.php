<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qca_flags', function (Blueprint $table) {
            $table->id();
            $table->string('flag_number', 50)->unique();
            $table->string('category', 50);
            $table->string('severity', 20);
            $table->string('status', 30)->default('open');
            $table->text('description');
            $table->unsignedBigInteger('assigned_to')->nullable();
            $table->unsignedBigInteger('raised_by');
            $table->string('target_entity_type', 50)->nullable();
            $table->unsignedBigInteger('target_entity_id')->nullable();
            $table->text('resolution_description')->nullable();
            $table->text('root_cause')->nullable();
            $table->text('evidence_of_correction')->nullable();
            $table->date('resolution_deadline')->nullable();
            $table->dateTime('resolved_at')->nullable();
            $table->unsignedBigInteger('resolved_by')->nullable();
            $table->text('resolution_notes')->nullable();
            $table->text('ceo_override_reason')->nullable();
            $table->unsignedBigInteger('ceo_overridden_by')->nullable();
            $table->dateTime('ceo_overridden_at')->nullable();
            $table->json('downstream_locks')->nullable();
            $table->string('source_module', 50)->nullable();
            $table->unsignedBigInteger('source_entity_id')->nullable();
            $table->timestamps();

            $table->foreign('assigned_to')->references('id')->on('staff')->nullOnDelete();
            $table->foreign('raised_by')->references('id')->on('staff')->restrictOnDelete();
            $table->foreign('resolved_by')->references('id')->on('staff')->nullOnDelete();
            $table->foreign('ceo_overridden_by')->references('id')->on('staff')->nullOnDelete();

            $table->index(['category', 'severity', 'status']);
            $table->index(['assigned_to']);
            $table->index(['target_entity_type', 'target_entity_id']);
            $table->index(['created_at']);
        });

        Schema::create('qca_milestones', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('qca_flag_id');
            $table->string('milestone_type', 50);
            $table->text('description');
            $table->unsignedBigInteger('recorded_by');
            $table->text('evidence')->nullable();
            $table->timestamps();

            $table->foreign('qca_flag_id')->references('id')->on('qca_flags')->cascadeOnDelete();
            $table->foreign('recorded_by')->references('id')->on('staff')->restrictOnDelete();

            $table->index(['qca_flag_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qca_milestones');
        Schema::dropIfExists('qca_flags');
    }
};
