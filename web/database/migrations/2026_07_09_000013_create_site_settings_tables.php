<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_settings', function (Blueprint $table) {
            $table->id();
            $table->string('setting_key', 100)->unique();
            $table->text('setting_value')->nullable();
            $table->string('value_type', 50)->default('string'); // string, number, boolean, json, file_path
            $table->string('group_name', 100)->nullable();
            $table->string('label', 200)->nullable();
            $table->string('description', 500)->nullable();
            $table->tinyInteger('is_public')->default(1);
            $table->tinyInteger('is_active')->default(1);
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->dateTime('updated_at')->nullable()->useCurrentOnUpdate();
        });

        Schema::create('social_links', function (Blueprint $table) {
            $table->id();
            $table->string('platform', 50); // twitter_x, linkedin, facebook, instagram, youtube, tiktok
            $table->string('display_name', 200);
            $table->string('url', 500);
            $table->string('icon_name', 50)->nullable();
            $table->integer('display_order')->default(0);
            $table->tinyInteger('is_active')->default(1);
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->nullable()->useCurrentOnUpdate();
        });

        Schema::create('navigation_menus', function (Blueprint $table) {
            $table->id();
            $table->string('menu_name', 100)->unique();
            $table->string('display_label', 200);
            $table->string('location', 50); // header, footer, sidebar, mobile_drawer
            $table->tinyInteger('is_active')->default(1);
            $table->integer('display_order')->default(0);
            $table->dateTime('created_at')->useCurrent();
        });

        Schema::create('navigation_menu_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('menu_id');
            $table->unsignedBigInteger('parent_item_id')->nullable();
            $table->string('label', 200);
            $table->string('label_sw', 200)->nullable();
            $table->string('url_or_route', 500)->nullable();
            $table->string('icon_name', 50)->nullable();
            $table->string('target', 20)->default('self');
            $table->tinyInteger('requires_auth')->default(0);
            $table->json('allowed_user_types')->nullable();
            $table->integer('display_order')->default(0);
            $table->tinyInteger('is_active')->default(1);
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->nullable()->useCurrentOnUpdate();
            $table->foreign('menu_id')->references('id')->on('navigation_menus')->restrictOnDelete();
            $table->foreign('parent_item_id')->references('id')->on('navigation_menu_items')->nullOnDelete();
        });

        Schema::create('contact_channels', function (Blueprint $table) {
            $table->id();
            $table->string('channel_type', 50); // email, phone, physical_address, social_media, fax
            $table->string('label', 200);
            $table->string('label_sw', 200)->nullable();
            $table->string('value', 500);
            $table->string('display_value', 500)->nullable();
            $table->string('department_scope', 50)->default('institution_wide');
            $table->tinyInteger('is_primary')->default(0);
            $table->integer('display_order')->default(0);
            $table->tinyInteger('is_active')->default(1);
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->nullable()->useCurrentOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_channels');
        Schema::dropIfExists('navigation_menu_items');
        Schema::dropIfExists('navigation_menus');
        Schema::dropIfExists('social_links');
        Schema::dropIfExists('site_settings');
    }
};
