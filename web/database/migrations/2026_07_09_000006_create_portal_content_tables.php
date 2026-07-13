<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('about_content_blocks', function (Blueprint $table) {
            $table->id();
            $table->string('block_key', 100)->unique();
            $table->string('title', 300);
            $table->string('subtitle', 500)->nullable();
            $table->longText('body');
            $table->string('featured_image_path', 500)->nullable();
            $table->integer('display_order')->default(0);
            $table->tinyInteger('is_active')->default(1);
            $table->dateTime('created_at')->useCurrent();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->dateTime('updated_at')->nullable()->useCurrentOnUpdate();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->foreign('created_by')->references('id')->on('staff')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('staff')->nullOnDelete();
        });

        Schema::create('institutional_timeline_events', function (Blueprint $table) {
            $table->id();
            $table->integer('year');
            $table->string('title', 300);
            $table->string('subtitle', 500)->nullable();
            $table->text('description');
            $table->string('image_path', 500)->nullable();
            $table->integer('display_order')->default(0);
            $table->tinyInteger('is_active')->default(1);
            $table->dateTime('created_at')->useCurrent();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->dateTime('updated_at')->nullable()->useCurrentOnUpdate();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->foreign('created_by')->references('id')->on('staff')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('staff')->nullOnDelete();
        });

        Schema::create('partner_logos', function (Blueprint $table) {
            $table->id();
            $table->string('partner_name', 300);
            $table->string('category', 50); // regulator, funder, county_body, ngo, corporate, other
            $table->string('logo_path', 500);
            $table->string('website_url', 500)->nullable();
            $table->integer('display_order')->default(0);
            $table->tinyInteger('is_active')->default(1);
            $table->dateTime('created_at')->useCurrent();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->dateTime('updated_at')->nullable()->useCurrentOnUpdate();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->foreign('created_by')->references('id')->on('staff')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('staff')->nullOnDelete();
        });

        Schema::create('media_attachments', function (Blueprint $table) {
            $table->id();
            $table->string('entity_type', 100);
            $table->unsignedBigInteger('entity_id');
            $table->string('file_path', 500);
            $table->string('file_type', 50); // image, pdf, video, document, audio
            $table->string('title', 300)->nullable();
            $table->string('caption', 500)->nullable();
            $table->integer('display_order')->default(0);
            $table->unsignedBigInteger('uploaded_by')->nullable();
            $table->dateTime('uploaded_at')->useCurrent();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->foreign('uploaded_by')->references('id')->on('staff')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('staff')->nullOnDelete();
            $table->index(['entity_type', 'entity_id']);
        });

        Schema::create('research_focus_areas', function (Blueprint $table) {
            $table->id();
            $table->string('name', 200);
            $table->text('description')->nullable();
            $table->string('icon_path', 500)->nullable();
            $table->integer('display_order')->default(0);
            $table->tinyInteger('is_active')->default(1);
            $table->dateTime('created_at')->useCurrent();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->dateTime('updated_at')->nullable()->useCurrentOnUpdate();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->foreign('created_by')->references('id')->on('staff')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('staff')->nullOnDelete();
        });

        Schema::create('research_projects', function (Blueprint $table) {
            $table->id();
            $table->string('title', 300);
            $table->string('subtitle', 500)->nullable();
            $table->string('status', 50); // ongoing, completed
            $table->unsignedBigInteger('focus_area_id')->nullable();
            $table->text('summary');
            $table->longText('abstract')->nullable();
            $table->string('cover_image_path', 500)->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->unsignedBigInteger('lead_researcher_id')->nullable();
            $table->tinyInteger('is_featured')->default(0);
            $table->dateTime('created_at')->useCurrent();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->dateTime('updated_at')->nullable()->useCurrentOnUpdate();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->foreign('focus_area_id')->references('id')->on('research_focus_areas')->nullOnDelete();
            $table->foreign('lead_researcher_id')->references('id')->on('staff')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('staff')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('staff')->nullOnDelete();
        });

        Schema::create('research_publications', function (Blueprint $table) {
            $table->id();
            $table->string('title', 300);
            $table->string('subtitle', 500)->nullable();
            $table->string('publication_type', 50); // policy_brief, peer_reviewed, field_data, toolkit
            $table->string('authors', 500)->nullable();
            $table->string('cover_image_path', 500)->nullable();
            $table->string('file_path', 500);
            $table->date('publish_date');
            $table->tinyInteger('is_downloadable')->default(1);
            $table->integer('download_count')->default(0);
            $table->dateTime('created_at')->useCurrent();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->dateTime('updated_at')->nullable()->useCurrentOnUpdate();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->foreign('created_by')->references('id')->on('staff')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('staff')->nullOnDelete();
        });

        Schema::create('partnership_requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_number', 50)->unique();
            $table->string('organization_name', 300);
            $table->string('organization_type', 50); // ngo, county_government, academic_institution, corporate, individual
            $table->string('contact_person', 200);
            $table->string('email', 255);
            $table->string('phone', 30);
            $table->text('proposed_scope');
            $table->string('target_sub_counties', 500)->nullable();
            $table->string('supporting_document_path', 500)->nullable();
            $table->string('status', 50)->default('pending_review'); // pending_review, under_evaluation, approved, declined
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->dateTime('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->tinyInteger('alert_sent_to_registrar')->default(0);
            $table->dateTime('created_at')->useCurrent();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->dateTime('updated_at')->nullable()->useCurrentOnUpdate();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->foreign('reviewed_by')->references('id')->on('staff')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('staff')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('staff')->nullOnDelete();
        });

        Schema::create('blog_posts', function (Blueprint $table) {
            $table->id();
            $table->string('title', 300);
            $table->string('subtitle', 500)->nullable();
            $table->string('slug', 300)->unique();
            $table->string('excerpt', 500)->nullable();
            $table->longText('body');
            $table->string('featured_image_path', 500)->nullable();
            $table->unsignedBigInteger('author_staff_id')->nullable();
            $table->string('status', 50)->default('draft'); // draft, in_review, published, archived
            $table->dateTime('published_at')->nullable();
            $table->integer('reading_time_minutes')->nullable();
            $table->integer('view_count')->default(0);
            $table->string('seo_meta_title', 300)->nullable();
            $table->string('seo_meta_description', 500)->nullable();
            $table->dateTime('created_at')->useCurrent();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->dateTime('updated_at')->nullable()->useCurrentOnUpdate();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->foreign('author_staff_id')->references('id')->on('staff')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('staff')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('staff')->nullOnDelete();
        });

        Schema::create('blog_post_revisions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('post_id');
            $table->integer('revision_number');
            $table->string('title_snapshot', 300);
            $table->longText('body_snapshot');
            $table->unsignedBigInteger('edited_by');
            $table->dateTime('edited_at')->useCurrent();
            $table->string('change_summary', 500)->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->foreign('post_id')->references('id')->on('blog_posts')->restrictOnDelete();
            $table->foreign('edited_by')->references('id')->on('staff')->restrictOnDelete();
            $table->foreign('created_by')->references('id')->on('staff')->nullOnDelete();
        });

        Schema::create('blog_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 200)->unique();
            $table->string('slug', 200)->unique();
            $table->text('description')->nullable();
            $table->integer('display_order')->default(0);
            $table->tinyInteger('is_active')->default(1);
            $table->dateTime('created_at')->useCurrent();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->dateTime('updated_at')->nullable()->useCurrentOnUpdate();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->foreign('created_by')->references('id')->on('staff')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('staff')->nullOnDelete();
        });

        Schema::create('blog_post_categories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('post_id');
            $table->unsignedBigInteger('category_id');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->dateTime('created_at')->useCurrent();
            $table->unique(['post_id', 'category_id']);
            $table->foreign('post_id')->references('id')->on('blog_posts')->restrictOnDelete();
            $table->foreign('category_id')->references('id')->on('blog_categories')->restrictOnDelete();
            $table->foreign('created_by')->references('id')->on('staff')->nullOnDelete();
        });

        Schema::create('gallery_albums', function (Blueprint $table) {
            $table->id();
            $table->string('album_name', 300);
            $table->string('subtitle', 500)->nullable();
            $table->string('cover_image_path', 500)->nullable();
            $table->integer('display_order')->default(0);
            $table->tinyInteger('is_active')->default(1);
            $table->dateTime('created_at')->useCurrent();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->dateTime('updated_at')->nullable()->useCurrentOnUpdate();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->foreign('created_by')->references('id')->on('staff')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('staff')->nullOnDelete();
        });

        Schema::create('gallery_images', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('album_id');
            $table->string('file_path', 500);
            $table->string('caption', 500)->nullable();
            $table->string('alt_text', 300)->nullable();
            $table->integer('display_order')->default(0);
            $table->tinyInteger('is_active')->default(1);
            $table->unsignedBigInteger('uploaded_by')->nullable();
            $table->dateTime('uploaded_at')->useCurrent();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->foreign('album_id')->references('id')->on('gallery_albums')->restrictOnDelete();
            $table->foreign('uploaded_by')->references('id')->on('staff')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('staff')->nullOnDelete();
        });

        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('title', 300);
            $table->string('subtitle', 500)->nullable();
            $table->string('event_type', 50); // graduation, health_drive, conference, exam_registration, open_day, outreach, community_event
            $table->longText('description')->nullable();
            $table->string('cover_image_path', 500)->nullable();
            $table->dateTime('start_datetime');
            $table->dateTime('end_datetime');
            $table->string('venue', 300)->nullable();
            $table->string('registration_url_or_form', 500)->nullable();
            $table->tinyInteger('is_public')->default(1);
            $table->tinyInteger('is_featured')->default(0);
            $table->dateTime('created_at')->useCurrent();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->dateTime('updated_at')->nullable()->useCurrentOnUpdate();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->foreign('created_by')->references('id')->on('staff')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('staff')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
        Schema::dropIfExists('gallery_images');
        Schema::dropIfExists('gallery_albums');
        Schema::dropIfExists('blog_post_categories');
        Schema::dropIfExists('blog_categories');
        Schema::dropIfExists('blog_post_revisions');
        Schema::dropIfExists('blog_posts');
        Schema::dropIfExists('partnership_requests');
        Schema::dropIfExists('research_publications');
        Schema::dropIfExists('research_projects');
        Schema::dropIfExists('research_focus_areas');
        Schema::dropIfExists('media_attachments');
        Schema::dropIfExists('partner_logos');
        Schema::dropIfExists('institutional_timeline_events');
        Schema::dropIfExists('about_content_blocks');
    }
};
