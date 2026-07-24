<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('rooms')) {
            Schema::create('rooms', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('campus_id');
                $table->string('room_code', 50);
                $table->string('room_name', 150);
                $table->unsignedSmallInteger('capacity')->default(30);
                $table->string('room_type', 50)->default('lecture');
                $table->tinyInteger('is_active')->default(1);
                $table->dateTime('created_at')->useCurrent();
                $table->foreign('campus_id')->references('id')->on('campuses')->restrictOnDelete();
                $table->unique(['campus_id', 'room_code']);
            });
        }

        if (! Schema::hasTable('program_timetable_templates')) {
            Schema::create('program_timetable_templates', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('program_id');
                $table->string('name', 120)->default('Default bell schedule');
                $table->tinyInteger('is_default')->default(1);
                $table->unsignedBigInteger('created_by')->nullable();
                $table->dateTime('created_at')->useCurrent();
                $table->dateTime('updated_at')->nullable()->useCurrentOnUpdate();
                $table->foreign('program_id')->references('id')->on('academic_programs')->cascadeOnDelete();
            });
        }

        if (! Schema::hasTable('program_timetable_template_days')) {
            Schema::create('program_timetable_template_days', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('template_id');
                $table->unsignedTinyInteger('day_of_week');
                $table->tinyInteger('is_active')->default(1);
                $table->foreign('template_id')->references('id')->on('program_timetable_templates')->cascadeOnDelete();
                $table->unique(['template_id', 'day_of_week'], 'ptt_day_unique');
            });
        }

        if (! Schema::hasTable('program_timetable_segments')) {
            Schema::create('program_timetable_segments', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('template_id');
                $table->string('label', 120);
                $table->time('start_time');
                $table->time('end_time');
                $table->string('segment_type', 50)->default('lesson');
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->foreign('template_id')->references('id')->on('program_timetable_templates')->cascadeOnDelete();
            });
        }

        if (! Schema::hasTable('program_timetables')) {
            Schema::create('program_timetables', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('program_id');
                $table->unsignedBigInteger('curriculum_version_id')->nullable();
                $table->unsignedTinyInteger('teaching_period')->default(1);
                $table->unsignedBigInteger('template_id')->nullable();
                $table->unsignedBigInteger('campus_id')->nullable();
                $table->string('status', 50)->default('draft');
                $table->text('generation_notes')->nullable();
                $table->dateTime('published_at')->nullable();
                $table->unsignedBigInteger('published_by')->nullable();
                $table->dateTime('created_at')->useCurrent();
                $table->dateTime('updated_at')->nullable()->useCurrentOnUpdate();
                $table->foreign('program_id')->references('id')->on('academic_programs')->cascadeOnDelete();
                $table->foreign('curriculum_version_id')->references('id')->on('curriculum_versions')->nullOnDelete();
                $table->foreign('template_id')->references('id')->on('program_timetable_templates')->nullOnDelete();
                $table->foreign('campus_id')->references('id')->on('campuses')->nullOnDelete();
                $table->index(['program_id', 'curriculum_version_id', 'teaching_period'], 'pt_scope_idx');
            });
        }

        if (! Schema::hasTable('program_timetable_sessions')) {
            Schema::create('program_timetable_sessions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('program_timetable_id');
                $table->unsignedBigInteger('unit_id')->nullable();
                $table->unsignedBigInteger('staff_id')->nullable();
                $table->unsignedBigInteger('room_id')->nullable();
                $table->unsignedTinyInteger('day_of_week');
                $table->time('start_time');
                $table->time('end_time');
                $table->string('session_type', 50)->default('lesson');
                $table->string('title', 200)->nullable();
                $table->string('venue', 200)->nullable();
                $table->string('class_group', 100)->nullable();
                $table->unsignedBigInteger('segment_id')->nullable();
                $table->foreign('program_timetable_id')->references('id')->on('program_timetables')->cascadeOnDelete();
                $table->foreign('unit_id')->references('id')->on('units')->nullOnDelete();
                $table->foreign('staff_id')->references('id')->on('staff')->nullOnDelete();
                $table->foreign('room_id')->references('id')->on('rooms')->nullOnDelete();
                $table->foreign('segment_id')->references('id')->on('program_timetable_segments')->nullOnDelete();
                $table->index(['program_timetable_id', 'day_of_week'], 'pts_day_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('program_timetable_sessions');
        Schema::dropIfExists('program_timetables');
        Schema::dropIfExists('program_timetable_segments');
        Schema::dropIfExists('program_timetable_template_days');
        Schema::dropIfExists('program_timetable_templates');
        Schema::dropIfExists('rooms');
    }
};
