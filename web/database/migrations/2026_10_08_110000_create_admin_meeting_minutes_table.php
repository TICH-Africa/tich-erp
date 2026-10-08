<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_meeting_minutes', function (Blueprint $table) {
            $table->id();
            $table->string('minute_code', 40)->unique();
            $table->string('title', 300);
            $table->date('meeting_date');
            $table->time('meeting_time')->nullable();
            $table->string('venue', 255)->nullable();
            $table->string('document_path', 500);
            $table->string('original_filename', 255)->nullable();
            $table->string('mime_type', 120)->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['meeting_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_meeting_minutes');
    }
};
