<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('faqs', function (Blueprint $table) {
            $table->id();
            $table->string('question', 500);
            $table->text('answer');
            $table->string('category', 100);
            $table->string('subcategory', 100)->nullable();
            $table->json('tags')->nullable();
            $table->string('language', 10)->default('en');
            $table->integer('view_count')->default(0);
            $table->tinyInteger('is_featured')->default(0);
            $table->tinyInteger('is_active')->default(1);
            $table->integer('display_order')->default(0);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->nullable()->useCurrentOnUpdate();
            $table->foreign('created_by')->references('id')->on('staff')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('staff')->nullOnDelete();
        });

        Schema::create('testimonials', function (Blueprint $table) {
            $table->id();
            $table->string('author_name', 300);
            $table->string('author_role', 50); // alumni, student, staff, partner, donor
            $table->string('author_program', 300)->nullable();
            $table->text('quote');
            $table->string('photo_path', 500)->nullable();
            $table->tinyInteger('is_featured')->default(0);
            $table->integer('display_order')->default(0);
            $table->tinyInteger('is_active')->default(1);
            $table->tinyInteger('consented')->default(0);
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->nullable()->useCurrentOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('testimonials');
        Schema::dropIfExists('faqs');
    }
};
