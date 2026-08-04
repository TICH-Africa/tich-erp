<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('offboarding_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('staff_id');
            $table->string('exit_type', 50); // resignation, retirement, non_renewal, termination, redundancy, death
            $table->string('status', 50)->default('pending'); // pending, approved, rejected, in_progress, completed
            $table->date('exit_date');
            $table->integer('notice_period_days')->nullable();
            $table->date('last_working_day')->nullable();
            $table->text('reason')->nullable();
            $table->text('termination_reason')->nullable();
            $table->unsignedBigInteger('initiated_by');
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->dateTime('approved_at')->nullable();
            $table->unsignedBigInteger('processed_by')->nullable();
            $table->dateTime('processed_at')->nullable();
            $table->text('notes')->nullable();
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->nullable()->useCurrentOnUpdate();

            $table->foreign('staff_id')->references('id')->on('staff')->restrictOnDelete();
            $table->foreign('initiated_by')->references('id')->on('staff')->restrictOnDelete();
            $table->foreign('approved_by')->references('id')->on('staff')->nullOnDelete();
            $table->foreign('processed_by')->references('id')->on('staff')->nullOnDelete();
            $table->index(['staff_id', 'status']);
            $table->index('exit_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offboarding_requests');
    }
};
