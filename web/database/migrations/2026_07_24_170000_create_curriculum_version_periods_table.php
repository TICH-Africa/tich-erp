<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('curriculum_version_periods')) {
            return;
        }

        Schema::create('curriculum_version_periods', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('curriculum_version_id');
            $table->unsignedTinyInteger('semester');
            $table->unsignedBigInteger('block_id')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->foreign('curriculum_version_id')->references('id')->on('curriculum_versions')->cascadeOnDelete();
            $table->foreign('block_id')->references('id')->on('nursing_blocks')->nullOnDelete();
            $table->unique(['curriculum_version_id', 'semester', 'block_id'], 'cv_period_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('curriculum_version_periods');
    }
};
