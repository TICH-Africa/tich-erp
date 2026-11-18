<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('efundi_opportunities', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('opportunity_type', 32);
            $table->string('organization_name', 255)->nullable();
            $table->string('location', 255)->nullable();
            $table->text('summary')->nullable();
            $table->longText('description')->nullable();
            $table->string('application_url', 500)->nullable();
            $table->string('application_email', 255)->nullable();
            $table->date('application_deadline')->nullable();
            $table->boolean('is_published')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->string('posted_via', 20);
            $table->foreignId('posted_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('students_notified_at')->nullable();
            $table->timestamps();

            $table->index(['is_published', 'application_deadline']);
            $table->index('opportunity_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('efundi_opportunities');
    }
};