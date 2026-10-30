<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('disciplinary_correspondences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('disciplinary_case_id')->constrained('disciplinary_cases')->cascadeOnDelete();
            $table->foreignId('recipient_staff_id')->constrained('staff')->cascadeOnDelete();
            $table->foreignId('sent_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('subject', 200);
            $table->text('body');
            $table->timestamp('emailed_at')->nullable();
            $table->timestamp('notified_at')->nullable();
            $table->timestamps();

            $table->index(['disciplinary_case_id', 'created_at'], 'disc_corr_case_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('disciplinary_correspondences');
    }
};
