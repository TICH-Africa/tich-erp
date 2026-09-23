<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('iqa_assessments')) {
            return;
        }

        Schema::create('iqa_assessments', function (Blueprint $table) {
            $table->id();
            $table->string('title', 200)->default('NATIONAL POLYTECHNIC QUALITY AUDIT TOOL');
            $table->unsignedSmallInteger('assessment_year')->nullable();
            $table->string('status', 20)->default('draft');
            $table->unsignedTinyInteger('current_section')->default(1);
            $table->json('payload');
            $table->unsignedBigInteger('created_by_user_id')->nullable();
            $table->unsignedBigInteger('updated_by_user_id')->nullable();
            $table->unsignedBigInteger('published_by_user_id')->nullable();
            $table->string('publisher_name', 200)->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'assessment_year'], 'iqa_status_year_idx');
            $table->foreign('created_by_user_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by_user_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('published_by_user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('iqa_assessments');
    }
};
