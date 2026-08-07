<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_profile_change_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('staff_id');
            $table->unsignedBigInteger('requested_by_user_id');
            $table->string('request_type', 50);
            $table->string('status', 30)->default('pending');
            $table->json('current_snapshot')->nullable();
            $table->json('proposed_changes');
            $table->string('attachment_path', 500)->nullable();
            $table->text('employee_notes')->nullable();
            $table->text('hr_notes')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->unsignedBigInteger('reviewed_by_staff_id')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->foreign('staff_id')->references('id')->on('staff')->cascadeOnDelete();
            $table->foreign('requested_by_user_id')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('reviewed_by_staff_id')->references('id')->on('staff')->nullOnDelete();
            $table->index(['staff_id', 'status']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_profile_change_requests');
    }
};
