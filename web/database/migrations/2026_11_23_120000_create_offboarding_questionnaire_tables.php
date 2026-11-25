<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('offboarding_questionnaire_templates', function (Blueprint $table) {
            $table->id();
            $table->string('title', 200);
            $table->text('description')->nullable();
            $table->json('employment_categories');
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('offboarding_questionnaire_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')->constrained('offboarding_questionnaire_templates')->cascadeOnDelete();
            $table->string('question_text', 1000);
            $table->string('input_type', 32)->default('textarea');
            $table->json('options')->nullable();
            $table->boolean('is_required')->default(true);
            $table->unsignedSmallInteger('display_order')->default(0);
            $table->timestamps();

            $table->index(['template_id', 'display_order'], 'ob_q_questions_tpl_order_idx');
        });

        Schema::create('offboarding_questionnaire_invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_id')->constrained('staff')->cascadeOnDelete();
            $table->foreignId('template_id')->constrained('offboarding_questionnaire_templates')->restrictOnDelete();
            $table->foreignId('staff_contract_id')->nullable()->constrained('staff_contracts')->nullOnDelete();
            $table->string('status', 32)->default('pending');
            $table->json('prefill_snapshot');
            $table->foreignId('sent_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('hr_acknowledged_at')->nullable();
            $table->timestamps();

            $table->index(['staff_id', 'status'], 'ob_q_inv_staff_status_idx');
            $table->index(['status', 'submitted_at'], 'ob_q_inv_status_sub_idx');
        });

        Schema::create('offboarding_questionnaire_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invitation_id')->constrained('offboarding_questionnaire_invitations')->cascadeOnDelete();
            $table->foreignId('question_id')->constrained('offboarding_questionnaire_questions')->cascadeOnDelete();
            $table->text('answer_text')->nullable();
            $table->timestamps();

            $table->unique(['invitation_id', 'question_id'], 'offboarding_q_answer_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offboarding_questionnaire_answers');
        Schema::dropIfExists('offboarding_questionnaire_invitations');
        Schema::dropIfExists('offboarding_questionnaire_questions');
        Schema::dropIfExists('offboarding_questionnaire_templates');
    }
};