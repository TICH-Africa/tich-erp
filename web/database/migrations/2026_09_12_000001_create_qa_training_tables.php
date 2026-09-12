<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qa_training_credits', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('staff_id');
            $table->string('credit_type', 30); // CPD, Mandatory, Certification
            $table->unsignedInteger('credit_value');
            $table->unsignedBigInteger('event_id')->nullable();
            $table->date('awarded_at');
            $table->date('expires_at')->nullable();
            $table->timestamps();

            $table->foreign('staff_id')->references('id')->on('staff')->restrictOnDelete();
            $table->foreign('event_id')->references('id')->on('qa_capacity_sessions')->nullOnDelete();

            $table->index(['staff_id', 'credit_type']);
            $table->index(['expires_at']);
        });

        Schema::create('qa_training_event_enrolments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('qa_capacity_session_id');
            $table->unsignedBigInteger('staff_id');
            $table->string('status', 30)->default('pending'); // pending, accepted, declined, deferred, attended, absent
            $table->text('decline_reason')->nullable();
            $table->timestamps();

            $table->foreign('qa_capacity_session_id')->references('id')->on('qa_capacity_sessions')->cascadeOnDelete();
            $table->foreign('staff_id')->references('id')->on('staff')->restrictOnDelete();

            $table->unique(['qa_capacity_session_id', 'staff_id'], 'qa_enrolment_unique');
        });

        Schema::create('qa_training_event_registrations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('qa_capacity_session_id');
            $table->string('title', 300);
            $table->text('description')->nullable();
            $table->dateTime('start_at');
            $table->dateTime('end_at')->nullable();
            $table->string('provider', 200)->nullable();
            $table->string('location', 300)->nullable();
            $table->string('credit_type', 30)->nullable();
            $table->unsignedInteger('credit_value')->nullable();
            $table->text('target_audience')->nullable();
            $table->unsignedInteger('max_attendees')->nullable();
            $table->unsignedInteger('enrolled_count')->default(0);
            $table->string('status', 30)->default('open'); // open, closed, cancelled
            $table->unsignedBigInteger('created_by');
            $table->timestamps();

            $table->foreign('created_by')->references('id')->on('staff')->restrictOnDelete();

            $table->index(['status', 'start_at']);
        });

        Schema::create('qa_certification_reminders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('staff_id');
            $table->string('certification_name', 200);
            $table->date('expiry_date');
            $table->date('reminder_sent_at')->nullable();
            $table->date('escalation_sent_at')->nullable();
            $table->boolean('is_expired')->default(false);
            $table->unsignedBigInteger('raised_by')->nullable();
            $table->timestamps();

            $table->foreign('staff_id')->references('id')->on('staff')->restrictOnDelete();
            $table->foreign('raised_by')->references('id')->on('staff')->nullOnDelete();

            $table->index(['expiry_date', 'is_expired']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qa_certification_reminders');
        Schema::dropIfExists('qa_training_event_registrations');
        Schema::dropIfExists('qa_training_event_enrolments');
        Schema::dropIfExists('qa_training_credits');
    }
};
