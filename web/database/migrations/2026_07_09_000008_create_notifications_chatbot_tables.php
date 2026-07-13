<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_templates', function (Blueprint $table) {
            $table->id();
            $table->string('template_key', 100)->unique();
            $table->string('event_type', 100);
            $table->string('title_template', 500);
            $table->text('body_template');
            $table->json('channels');
            $table->string('priority', 20)->default('normal'); // low, normal, high, urgent
            $table->tinyInteger('is_active')->default(1);
            $table->string('language', 10)->default('en'); // en, sw
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->nullable()->useCurrentOnUpdate();
        });

        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('template_id')->nullable();
            $table->string('title', 500);
            $table->text('body');
            $table->json('channels_sent');
            $table->string('related_entity_type', 100)->nullable();
            $table->string('related_entity_id', 50)->nullable();
            $table->tinyInteger('is_read')->default(0);
            $table->dateTime('read_at')->nullable();
            $table->string('read_device_info', 500)->nullable();
            $table->string('priority', 20)->default('normal');
            $table->tinyInteger('is_dismissed')->default(0);
            $table->dateTime('created_at')->useCurrent();
            $table->foreign('user_id')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('template_id')->references('id')->on('notification_templates')->nullOnDelete();
            $table->index('user_id');
            $table->index(['user_id', 'is_read']);
            $table->index('created_at');
        });

        Schema::create('device_tokens', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('device_token', 500);
            $table->string('platform', 50); // ios, android, web
            $table->string('device_name', 200)->nullable();
            $table->string('app_version', 20)->nullable();
            $table->tinyInteger('is_active')->default(1);
            $table->dateTime('last_used_at')->nullable();
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->nullable()->useCurrentOnUpdate();
            $table->unique(['user_id', 'device_token', 'platform'], 'device_tokens_unique');
            $table->foreign('user_id')->references('id')->on('users')->restrictOnDelete();
        });

        Schema::create('sms_gateway_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('notification_id')->nullable();
            $table->string('recipient_phone', 30);
            $table->text('message_body');
            $table->string('provider', 50); // mpesa, africa_talking, bulk_sms_provider
            $table->string('provider_message_id', 100)->nullable();
            $table->integer('segments')->default(1);
            $table->string('status', 50)->default('queued'); // queued, sent, delivered, failed, rejected, unknown
            $table->string('status_code', 50)->nullable();
            $table->string('error_message', 500)->nullable();
            $table->decimal('cost_KES', 10, 2)->default(0.00);
            $table->dateTime('dispatched_at')->nullable();
            $table->dateTime('delivered_at')->nullable();
            $table->dateTime('created_at')->useCurrent();
            $table->foreign('notification_id')->references('id')->on('notifications')->nullOnDelete();
            $table->index('notification_id');
            $table->index('recipient_phone');
            $table->index('created_at');
        });

        Schema::create('email_gateway_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('notification_id')->nullable();
            $table->string('recipient_email', 255);
            $table->string('subject', 500);
            $table->string('body_preview', 500)->nullable();
            $table->string('provider', 100);
            $table->string('provider_message_id', 200)->nullable();
            $table->string('status', 50)->default('queued'); // queued, sent, delivered, bounced, complained, failed
            $table->string('bounce_reason', 500)->nullable();
            $table->string('complaint_reason', 500)->nullable();
            $table->dateTime('opened_at')->nullable();
            $table->dateTime('clicked_at')->nullable();
            $table->dateTime('dispatched_at')->nullable();
            $table->dateTime('created_at')->useCurrent();
            $table->foreign('notification_id')->references('id')->on('notifications')->nullOnDelete();
            $table->index('notification_id');
            $table->index('recipient_email');
            $table->index('created_at');
        });

        Schema::create('chatbot_conversations', function (Blueprint $table) {
            $table->id();
            $table->string('session_id', 100)->unique();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('user_type', 50)->nullable(); // student, staff, applicant, guest
            $table->string('language', 10)->default('en');
            $table->string('device_type', 50)->default('mobile'); // mobile, web, desktop
            $table->tinyInteger('escalated_to_human')->default(0);
            $table->unsignedBigInteger('escalated_to_user_id')->nullable();
            $table->dateTime('escalated_at')->nullable();
            $table->integer('satisfaction_rating')->nullable();
            $table->dateTime('started_at');
            $table->dateTime('ended_at')->nullable();
            $table->integer('message_count')->default(0);
            $table->dateTime('created_at')->useCurrent();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('escalated_to_user_id')->references('id')->on('staff')->nullOnDelete();
        });

        Schema::create('chatbot_messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('conversation_id');
            $table->string('sender', 50); // user, bot, human_agent
            $table->text('message_body');
            $table->string('intent_detected', 100)->nullable();
            $table->decimal('intent_confidence', 5, 2)->nullable();
            $table->json('entities_extracted')->nullable();
            $table->string('quick_reply_selected', 500)->nullable();
            $table->tinyInteger('is_escalation_trigger')->default(0);
            $table->string('escalation_reason', 500)->nullable();
            $table->unsignedBigInteger('human_agent_id')->nullable();
            $table->dateTime('created_at')->useCurrent();
            $table->foreign('conversation_id')->references('id')->on('chatbot_conversations')->restrictOnDelete();
            $table->foreign('human_agent_id')->references('id')->on('staff')->nullOnDelete();
            $table->index('conversation_id');
            $table->index('intent_detected');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chatbot_messages');
        Schema::dropIfExists('chatbot_conversations');
        Schema::dropIfExists('email_gateway_logs');
        Schema::dropIfExists('sms_gateway_logs');
        Schema::dropIfExists('device_tokens');
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('notification_templates');
    }
};
