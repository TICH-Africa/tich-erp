<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hr_policies', function (Blueprint $table) {
            $table->id();
            $table->string('title', 200);
            $table->string('slug', 200)->unique();
            $table->text('description')->nullable();
            $table->string('file_path', 500);
            $table->string('original_filename', 255);
            $table->string('mime_type', 100);
            $table->integer('file_size')->nullable();
            $table->string('category', 100)->default('general');
            $table->date('effective_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->tinyInteger('is_active')->default(1);
            $table->text('tags')->nullable();
            $table->unsignedBigInteger('uploaded_by');
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->nullable()->useCurrentOnUpdate();

            $table->foreign('uploaded_by')->references('id')->on('staff')->restrictOnDelete();
            $table->index('category');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_policies');
    }
};
