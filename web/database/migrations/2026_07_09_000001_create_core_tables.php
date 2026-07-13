<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campuses', function (Blueprint $table) {
            $table->id();
            $table->string('campus_code', 20)->unique();
            $table->string('campus_name', 200);
            $table->string('campus_type', 50); // main, community_college, sub_county_hub
            $table->unsignedBigInteger('parent_campus_id')->nullable();
            $table->string('county', 100)->nullable();
            $table->string('sub_county', 100)->nullable();
            $table->string('physical_address', 500)->nullable();
            $table->tinyInteger('is_active')->default(1);
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->nullable()->useCurrentOnUpdate();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->foreign('parent_campus_id')->references('id')->on('campuses')->nullOnDelete();
        });

        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->string('dept_code', 20)->unique();
            $table->string('dept_name', 200);
            $table->string('dept_category', 50); // academic, administrative, support
            $table->unsignedBigInteger('hod_id')->nullable();
            $table->unsignedBigInteger('parent_dept_id')->nullable();
            $table->unsignedBigInteger('campus_id')->nullable();
            $table->tinyInteger('is_active')->default(1);
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->nullable()->useCurrentOnUpdate();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->foreign('parent_dept_id')->references('id')->on('departments')->nullOnDelete();
            $table->foreign('campus_id')->references('id')->on('campuses')->nullOnDelete();
        });

        Schema::create('academic_programs', function (Blueprint $table) {
            $table->id();
            $table->string('program_code', 30)->unique();
            $table->string('program_name', 300);
            $table->string('program_type', 50); // certificate, diploma, higher_diploma, artisan, short_course
            $table->string('regulatory_body', 50)->nullable(); // NITA, CDACC, TVET, NURSING_COUNCIL, NONE
            $table->unsignedBigInteger('department_id');
            $table->integer('duration_months')->nullable();
            $table->integer('semester_count')->default(0);
            $table->integer('block_count')->default(0);
            $table->tinyInteger('is_nursing_track')->default(0);
            $table->decimal('min_attendance_pct', 5, 2)->default(90.00);
            $table->decimal('theory_pass_mark', 5, 2)->default(40.00);
            $table->decimal('clinical_pass_mark', 5, 2)->default(50.00);
            $table->string('status', 50)->default('pending_ceo'); // pending_ceo, active, inactive, archived
            $table->dateTime('approved_by_ceo_at')->nullable();
            $table->unsignedBigInteger('approved_by_ceo_id')->nullable();
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->nullable()->useCurrentOnUpdate();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->foreign('department_id')->references('id')->on('departments')->restrictOnDelete();
        });

        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->string('unit_code', 30)->unique();
            $table->string('unit_name', 300);
            $table->unsignedBigInteger('program_id');
            $table->integer('semester')->default(1);
            $table->integer('block')->nullable();
            $table->decimal('credit_hours', 5, 2)->default(0);
            $table->integer('contact_hours')->default(0);
            $table->tinyInteger('is_core')->default(1);
            $table->tinyInteger('is_practical')->default(0);
            $table->unsignedBigInteger('prerequisite_unit_id')->nullable();
            $table->unsignedBigInteger('co_requisite_unit_id')->nullable();
            $table->decimal('assessment_weight_attendance_pct', 5, 2)->default(5.00);
            $table->decimal('assessment_weight_cat_pct', 5, 2)->default(30.00);
            $table->decimal('assessment_weight_practical_pct', 5, 2)->default(0.00);
            $table->decimal('assessment_weight_exam_pct', 5, 2)->default(60.00);
            $table->string('status', 50)->default('pending_registry');
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->nullable()->useCurrentOnUpdate();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->foreign('program_id')->references('id')->on('academic_programs')->restrictOnDelete();
            $table->foreign('prerequisite_unit_id')->references('id')->on('units')->nullOnDelete();
            $table->foreign('co_requisite_unit_id')->references('id')->on('units')->nullOnDelete();
        });

        Schema::create('academic_years', function (Blueprint $table) {
            $table->id();
            $table->string('year_label', 20)->unique();
            $table->date('start_date');
            $table->date('end_date');
            $table->tinyInteger('is_current')->default(0);
            $table->dateTime('created_at')->useCurrent();
            $table->unsignedBigInteger('created_by')->nullable();
        });

        Schema::create('semesters', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('academic_year_id');
            $table->string('semester_label', 20);
            $table->integer('semester_number');
            $table->string('intake_month', 20)->nullable();
            $table->date('start_date');
            $table->date('end_date');
            $table->date('registration_open_date')->nullable();
            $table->date('registration_close_date')->nullable();
            $table->tinyInteger('is_current')->default(0);
            $table->dateTime('created_at')->useCurrent();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->foreign('academic_year_id')->references('id')->on('academic_years')->restrictOnDelete();
        });

        Schema::create('nursing_blocks', function (Blueprint $table) {
            $table->id();
            $table->string('block_label', 50);
            $table->integer('block_order');
            $table->integer('duration_months')->default(4);
            $table->unsignedBigInteger('program_id');
            $table->tinyInteger('is_active')->default(1);
            $table->dateTime('created_at')->useCurrent();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->foreign('program_id')->references('id')->on('academic_programs')->restrictOnDelete();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('action', 100);
            $table->string('entity_type', 100);
            $table->string('entity_id', 50);
            $table->json('old_value')->nullable();
            $table->json('new_value')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->string('reason', 500)->nullable();
            $table->dateTime('created_at')->useCurrent();
            $table->index(['entity_type', 'entity_id']);
            $table->index('user_id');
            $table->index('action');
            $table->index('created_at');
        });

        Schema::create('communication_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('recipient_user_id')->nullable();
            $table->string('recipient_email', 255)->nullable();
            $table->string('recipient_phone', 30)->nullable();
            $table->string('channel', 20); // email, sms, whatsapp, push
            $table->string('template_key', 100)->nullable();
            $table->string('subject', 500)->nullable();
            $table->string('body_preview', 500)->nullable();
            $table->string('status', 20)->default('queued'); // queued, sent, delivered, failed
            $table->dateTime('sent_at')->nullable();
            $table->dateTime('created_at')->useCurrent();
            $table->unsignedBigInteger('created_by')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('communication_logs');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('nursing_blocks');
        Schema::dropIfExists('semesters');
        Schema::dropIfExists('academic_years');
        Schema::dropIfExists('units');
        Schema::dropIfExists('academic_programs');
        Schema::dropIfExists('departments');
        Schema::dropIfExists('campuses');
    }
};
