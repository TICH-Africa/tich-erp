<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('unit_contents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('unit_id');
            $table->unsignedBigInteger('unit_allocation_id')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->string('title', 300);
            $table->string('content_type', 50)->default('lesson_note'); // lesson_note, video, document, link
            $table->text('content_text')->nullable();
            $table->string('file_path')->nullable();
            $table->string('original_filename')->nullable();
            $table->string('mime_type')->nullable();
            $table->integer('file_size')->nullable();
            $table->string('external_url')->nullable();
            $table->string('status', 50)->default('draft'); // draft, published, archived
            $table->dateTime('published_at')->nullable();
            $table->dateTime('available_from')->nullable();
            $table->dateTime('available_until')->nullable();
            $table->integer('display_order')->default(0);
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->nullable()->useCurrentOnUpdate();
            $table->foreign('unit_id')->references('id')->on('units')->restrictOnDelete();
            $table->foreign('unit_allocation_id')->references('id')->on('unit_allocations')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('staff')->restrictOnDelete();
            $table->index(['unit_id', 'status', 'display_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unit_contents');
    }
};
