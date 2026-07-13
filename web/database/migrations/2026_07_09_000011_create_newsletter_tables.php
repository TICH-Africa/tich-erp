<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('newsletter_subscribers', function (Blueprint $table) {
            $table->id();
            $table->string('email', 255)->unique();
            $table->string('subscriber_name', 200)->nullable();
            $table->string('subscriber_type', 50)->default('guest'); // student, staff, alumni, applicant, guest
            $table->unsignedBigInteger('linked_user_id')->nullable();
            $table->dateTime('subscribed_at')->useCurrent();
            $table->string('unsubscribe_token', 100)->unique();
            $table->tinyInteger('is_active')->default(1);
            $table->tinyInteger('is_confirmed')->default(0);
            $table->dateTime('confirmed_at')->nullable();
            $table->dateTime('unsubscribed_at')->nullable();
            $table->dateTime('last_sent_at')->nullable();
            $table->string('source', 100)->nullable();
            $table->foreign('linked_user_id')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('newsletter_campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('campaign_name', 300);
            $table->string('subject', 500);
            $table->string('preview_text', 200)->nullable();
            $table->longText('body');
            $table->string('sender_name', 200);
            $table->string('sender_email', 255);
            $table->string('target_segment', 50)->default('all_active'); // all_active, students, staff, alumni, applicants, custom_filter
            $table->json('custom_filter_json')->nullable();
            $table->integer('recipient_count')->default(0);
            $table->dateTime('scheduled_at')->nullable();
            $table->dateTime('sent_at')->nullable();
            $table->string('status', 50)->default('draft'); // draft, scheduled, sending, sent, failed, cancelled
            $table->unsignedBigInteger('sent_by');
            $table->integer('opens_count')->default(0);
            $table->integer('clicks_count')->default(0);
            $table->integer('unsubscribes_count')->default(0);
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->nullable()->useCurrentOnUpdate();
            $table->foreign('sent_by')->references('id')->on('staff')->restrictOnDelete();
        });

        Schema::create('newsletter_campaign_recipients', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('campaign_id');
            $table->unsignedBigInteger('subscriber_id');
            $table->string('email', 255);
            $table->string('status', 50)->default('queued'); // queued, sent, delivered, opened, clicked, bounced, unsubscribed
            $table->dateTime('opened_at')->nullable();
            $table->dateTime('clicked_at')->nullable();
            $table->string('bounced_reason', 500)->nullable();
            $table->dateTime('created_at')->useCurrent();
            $table->unique(['campaign_id', 'subscriber_id'], 'newsletter_campaign_recipients_unique');
            $table->foreign('campaign_id')->references('id')->on('newsletter_campaigns')->restrictOnDelete();
            $table->foreign('subscriber_id')->references('id')->on('newsletter_subscribers')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('newsletter_campaign_recipients');
        Schema::dropIfExists('newsletter_campaigns');
        Schema::dropIfExists('newsletter_subscribers');
    }
};
