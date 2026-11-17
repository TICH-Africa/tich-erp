<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('research_resources', function (Blueprint $table) {
            $table->id();
            $table->string('title', 500);
            $table->string('authors', 500)->nullable();
            $table->string('publisher', 300)->nullable();
            $table->string('year', 20)->nullable();
            $table->string('isbn', 150)->nullable();
            $table->string('doi', 300)->nullable();
            $table->text('description')->nullable();
            $table->string('file_path', 500)->nullable();
            $table->string('original_name', 255)->nullable();
            $table->string('mime_type', 150)->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('research_resources');
    }
};
