<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mobile_app_releases', function (Blueprint $table) {
            $table->id();
            $table->string('platform', 20);
            $table->string('version_label', 50);
            $table->unsignedInteger('build_number')->nullable();
            $table->string('storage_path', 500)->nullable();
            $table->string('original_filename', 255)->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->string('external_url', 500)->nullable();
            $table->text('release_notes')->nullable();
            $table->boolean('is_published')->default(true);
            $table->foreignId('uploaded_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['platform', 'is_published', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mobile_app_releases');
    }
};