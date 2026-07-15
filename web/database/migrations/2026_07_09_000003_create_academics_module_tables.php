<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('program_units', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('program_id');
            $table->unsignedBigInteger('unit_id');
            $table->integer('semester');
            $table->unsignedBigInteger('block_id')->nullable();
            $table->tinyInteger('is_compulsory')->default(1);
            $table->tinyInteger('is_active')->default(1);
            $table->unique(['program_id', 'unit_id'], 'prog_unit_unique');
            $table->foreign('program_id')->references('id')->on('academic_programs')->restrictOnDelete();
            $table->foreign('unit_id')->references('id')->on('units')->restrictOnDelete();
            $table->foreign('block_id')->references('id')->on('nursing_blocks')->nullOnDelete();
        });

        Schema::create('unit_allocations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('unit_id');
            $table->unsignedBigInteger('staff_id');
            $table->unsignedBigInteger('semester_id');
            $table->unsignedBigInteger('campus_id');
            $table->tinyInteger('is_coordinator')->default(0);
            $table->integer('contact_hours_assigned')->default(0);
            $table->tinyInteger('is_active')->default(1);
            $table->foreign('unit_id')->references('id')->on('units')->restrictOnDelete();
            $table->foreign('staff_id')->references('id')->on('staff')->restrictOnDelete();
            $table->foreign('semester_id')->references('id')->on('semesters')->restrictOnDelete();
            $table->foreign('campus_id')->references('id')->on('campuses')->restrictOnDelete();
            $table->index(['unit_id', 'semester_id']);
            $table->index('staff_id');
        });

        Schema::create('lesson_plans', function (Blueprint $table) {
            $table->id();
            $table->string('plan_number', 50)->unique();
            $table->unsignedBigInteger('unit_allocation_id');
            $table->unsignedBigInteger('prepared_by');
            $table->text('lesson_objectives');
            $table->text('topics_covered')->nullable();
            $table->text('competencies_targeted')->nullable();
            $table->integer('contact_hours');
            $table->integer('week_number');
            $table->date('planned_date');
            $table->string('teaching_methods', 500)->nullable();
            $table->string('resources_required', 500)->nullable();
            $table->string('status', 50)->default('draft'); // draft, submitted, approved, rejected, modified
            $table->text('hod_comments')->nullable();
            $table->unsignedBigInteger('hod_id')->nullable();
            $table->dateTime('hod_action_at')->nullable();
            $table->tinyInteger('registrar_visible')->default(0);
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->nullable()->useCurrentOnUpdate();
            $table->foreign('unit_allocation_id')->references('id')->on('unit_allocations')->restrictOnDelete();
            $table->foreign('prepared_by')->references('id')->on('staff')->restrictOnDelete();
            $table->foreign('hod_id')->references('id')->on('staff')->nullOnDelete();
        });

        Schema::create('lesson_plan_approvals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('lesson_plan_id');
            $table->unsignedBigInteger('approver_id');
            $table->string('approval_level', 50); // hod, registrar, academic_board
            $table->string('decision', 50); // approved, rejected, request_modification
            $table->text('comments')->nullable();
            $table->dateTime('decided_at')->useCurrent();
            $table->foreign('lesson_plan_id')->references('id')->on('lesson_plans')->restrictOnDelete();
            $table->foreign('approver_id')->references('id')->on('staff')->restrictOnDelete();
        });

        Schema::create('attendance_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('session_number', 50)->unique();
            $table->unsignedBigInteger('unit_allocation_id');
            $table->date('session_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->string('venue', 200)->nullable();
            $table->string('session_type', 50)->default('physical'); // physical, virtual, field_practical, clinical
            $table->string('virtual_meeting_url', 500)->nullable();
            $table->tinyInteger('is_mandatory')->default(1);
            $table->integer('total_expected_attendees')->default(0);
            $table->string('signed_sheet_image_path', 500)->nullable();
            $table->unsignedBigInteger('recorded_by');
            $table->dateTime('recorded_at')->useCurrent();
            $table->tinyInteger('is_locked')->default(0);
            $table->foreign('unit_allocation_id')->references('id')->on('unit_allocations')->restrictOnDelete();
            $table->foreign('recorded_by')->references('id')->on('staff')->restrictOnDelete();
        });

        Schema::create('attendance_records', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('session_id');
            $table->unsignedBigInteger('student_id');
            $table->tinyInteger('is_present')->default(0);
            $table->time('sign_in_time')->nullable();
            $table->tinyInteger('recorded_by_tutor')->default(0);
            $table->tinyInteger('verified_by_hod')->default(0);
            $table->text('verification_note')->nullable();
            $table->dateTime('created_at')->useCurrent();
            $table->unique(['session_id', 'student_id'], 'att_rec_unique');
            $table->foreign('session_id')->references('id')->on('attendance_sessions')->restrictOnDelete();
            $table->foreign('student_id')->references('id')->on('students')->restrictOnDelete();
            $table->index('student_id');
        });

        Schema::create('attendance_summaries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('unit_id');
            $table->unsignedBigInteger('semester_id');
            $table->integer('total_sessions')->default(0);
            $table->integer('total_present')->default(0);
            $table->decimal('attendance_percentage', 5, 2)->default(0.00);
            $table->string('status_flag', 20)->default('green'); // green, amber, red
            $table->dateTime('last_calculated_at');
            $table->unique(['student_id', 'unit_id', 'semester_id'], 'att_sum_unique');
            $table->foreign('student_id')->references('id')->on('students')->restrictOnDelete();
            $table->foreign('unit_id')->references('id')->on('units')->restrictOnDelete();
            $table->foreign('semester_id')->references('id')->on('semesters')->restrictOnDelete();
        });

        Schema::create('student_semester_registrations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('semester_id');
            $table->date('registration_date');
            $table->unsignedBigInteger('registered_by')->nullable();
            $table->string('registration_type', 50)->default('self'); // self, admin, bulk
            $table->integer('unit_count')->default(0);
            $table->string('status', 50)->default('registered');
            $table->tinyInteger('is_fee_cleared')->default(0);
            $table->unique(['student_id', 'semester_id'], 'sem_reg_unique');
            $table->foreign('student_id')->references('id')->on('students')->restrictOnDelete();
            $table->foreign('semester_id')->references('id')->on('semesters')->restrictOnDelete();
            $table->foreign('registered_by')->references('id')->on('staff')->nullOnDelete();
        });

        Schema::create('registered_units', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('semester_registration_id');
            $table->unsignedBigInteger('unit_id');
            $table->tinyInteger('is_additional')->default(0);
            $table->dateTime('created_at')->useCurrent();
            $table->unique(['semester_registration_id', 'unit_id'], 'reg_units_unique');
            $table->foreign('semester_registration_id')->references('id')->on('student_semester_registrations')->restrictOnDelete();
            $table->foreign('unit_id')->references('id')->on('units')->restrictOnDelete();
        });

        Schema::create('deferral_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('requested_semester_id');
            $table->text('reason');
            $table->string('supporting_document_path', 500)->nullable();
            $table->string('status', 50)->default('pending'); // pending, approved, rejected
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->dateTime('reviewed_at')->nullable();
            $table->text('review_comments')->nullable();
            $table->unsignedBigInteger('effective_from_semester_id')->nullable();
            $table->dateTime('created_at')->useCurrent();
            $table->foreign('student_id')->references('id')->on('students')->restrictOnDelete();
            $table->foreign('requested_semester_id')->references('id')->on('semesters')->restrictOnDelete();
            $table->foreign('effective_from_semester_id')->references('id')->on('semesters')->nullOnDelete();
            $table->foreign('reviewed_by')->references('id')->on('staff')->nullOnDelete();
        });

        Schema::create('waitlist_entries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('applicant_id');
            $table->unsignedBigInteger('program_id');
            $table->string('cohort_preference', 20)->nullable();
            $table->integer('position');
            $table->tinyInteger('enrolled_from_waitlist')->default(0);
            $table->dateTime('enrolled_at')->nullable();
            $table->dateTime('created_at')->useCurrent();
            $table->foreign('applicant_id')->references('id')->on('applicants')->restrictOnDelete();
            $table->foreign('program_id')->references('id')->on('academic_programs')->restrictOnDelete();
        });

        Schema::create('cat_scores', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('unit_id');
            $table->unsignedBigInteger('semester_id');
            $table->string('assessment_type', 50); // cat, assignment, practical, project, field_log, skills_lab
            $table->string('assessment_name', 200);
            $table->decimal('max_score', 5, 2);
            $table->decimal('score_obtained', 5, 2);
            $table->decimal('percentage_score', 5, 2)->default(0.00);
            $table->decimal('weight_in_final', 5, 2)->default(0.00);
            $table->unsignedBigInteger('recorded_by');
            $table->tinyInteger('verified_by_hod')->default(0);
            $table->dateTime('recorded_at')->useCurrent();
            $table->dateTime('updated_at')->nullable()->useCurrentOnUpdate();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->foreign('student_id')->references('id')->on('students')->restrictOnDelete();
            $table->foreign('unit_id')->references('id')->on('units')->restrictOnDelete();
            $table->foreign('semester_id')->references('id')->on('semesters')->restrictOnDelete();
            $table->foreign('recorded_by')->references('id')->on('staff')->restrictOnDelete();
            $table->index(['student_id', 'unit_id', 'semester_id']);
        });

        Schema::create('exam_eligibility_matrix', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('unit_id');
            $table->unsignedBigInteger('semester_id');
            $table->tinyInteger('attendance_check_passed')->default(0);
            $table->decimal('attendance_percentage', 5, 2)->default(0.00);
            $table->tinyInteger('fee_clearance_check_passed')->default(0);
            $table->tinyInteger('invigilator_assigned')->default(0);
            $table->tinyInteger('exam_card_issued')->default(0);
            $table->tinyInteger('eligible_for_exams')->default(0);
            $table->dateTime('calculated_at');
            $table->dateTime('created_at')->useCurrent();
            $table->unique(['student_id', 'unit_id', 'semester_id']);
            $table->foreign('student_id')->references('id')->on('students')->restrictOnDelete();
            $table->foreign('unit_id')->references('id')->on('units')->restrictOnDelete();
            $table->foreign('semester_id')->references('id')->on('semesters')->restrictOnDelete();
        });

        Schema::create('exam_cards', function (Blueprint $table) {
            $table->id();
            $table->string('exam_card_number', 50)->unique();
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('semester_id');
            $table->string('examination_number', 50)->nullable();
            $table->string('qr_code_data', 500)->nullable();
            $table->dateTime('issued_at');
            $table->tinyInteger('is_voided')->default(0);
            $table->string('voided_reason', 500)->nullable();
            $table->unsignedBigInteger('voided_by')->nullable();
            $table->unique(['student_id', 'semester_id'], 'exam_card_unique');
            $table->foreign('student_id')->references('id')->on('students')->restrictOnDelete();
            $table->foreign('semester_id')->references('id')->on('semesters')->restrictOnDelete();
            $table->foreign('voided_by')->references('id')->on('staff')->nullOnDelete();
        });

        Schema::create('exam_schedules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('unit_id');
            $table->unsignedBigInteger('semester_id');
            $table->date('exam_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->string('venue', 200);
            $table->string('exam_type', 50)->default('main'); // main, supplementary, special, clinical
            $table->unsignedBigInteger('invigilator_id')->nullable();
            $table->unsignedBigInteger('second_invigilator_id')->nullable();
            $table->integer('total_candidates')->default(0);
            $table->string('status', 50)->default('scheduled'); // scheduled, in_progress, completed, cancelled
            $table->foreign('unit_id')->references('id')->on('units')->restrictOnDelete();
            $table->foreign('semester_id')->references('id')->on('semesters')->restrictOnDelete();
            $table->foreign('invigilator_id')->references('id')->on('staff')->nullOnDelete();
            $table->foreign('second_invigilator_id')->references('id')->on('staff')->nullOnDelete();
            $table->index(['unit_id', 'semester_id']);
        });

        Schema::create('exam_results', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('exam_card_id');
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('unit_id');
            $table->unsignedBigInteger('semester_id');
            $table->decimal('cat_total', 5, 2)->default(0.00);
            $table->decimal('practical_total', 5, 2)->default(0.00);
            $table->decimal('final_exam_score', 5, 2)->default(0.00);
            $table->decimal('final_total_score', 5, 2)->default(0.00);
            $table->string('grade_letter', 5)->nullable();
            $table->decimal('grade_points', 5, 2)->nullable();
            $table->tinyInteger('theory_pass_check')->default(0);
            $table->tinyInteger('clinical_pass_check')->default(0);
            $table->tinyInteger('is_supplementary')->default(0);
            $table->tinyInteger('supplementary_triggered')->default(0);
            $table->tinyInteger('clinical_supplementary_triggered')->default(0);
            $table->tinyInteger('is_special_exam')->default(0);
            $table->tinyInteger('is_remark_requested')->default(0);
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('moderator_id')->nullable();
            $table->dateTime('moderated_at')->nullable();
            $table->tinyInteger('board_approved')->default(0);
            $table->dateTime('board_approved_at')->nullable();
            $table->tinyInteger('is_published')->default(0);
            $table->dateTime('published_at')->nullable();
            $table->unsignedBigInteger('entered_by');
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->nullable()->useCurrentOnUpdate();
            $table->foreign('exam_card_id')->references('id')->on('exam_cards')->restrictOnDelete();
            $table->foreign('student_id')->references('id')->on('students')->restrictOnDelete();
            $table->foreign('unit_id')->references('id')->on('units')->restrictOnDelete();
            $table->foreign('semester_id')->references('id')->on('semesters')->restrictOnDelete();
            $table->foreign('moderator_id')->references('id')->on('staff')->nullOnDelete();
            $table->foreign('entered_by')->references('id')->on('staff')->restrictOnDelete();
            $table->index(['student_id', 'semester_id']);
            $table->index(['unit_id', 'semester_id']);
        });

        Schema::create('supplementary_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('exam_result_id');
            $table->string('supplementary_type', 50); // theory, clinical, both
            $table->decimal('fee_amount', 12, 2);
            $table->tinyInteger('fee_paid')->default(0);
            $table->string('fee_payment_ref', 100)->nullable();
            $table->dateTime('fee_paid_at')->nullable();
            $table->string('application_status', 50)->default('pending_fee'); // pending_fee, scheduled, completed, cancelled
            $table->unsignedBigInteger('scheduled_exam_id')->nullable();
            $table->decimal('new_score', 5, 2)->nullable();
            $table->dateTime('created_at')->useCurrent();
            $table->foreign('student_id')->references('id')->on('students')->restrictOnDelete();
            $table->foreign('exam_result_id')->references('id')->on('exam_results')->restrictOnDelete();
            $table->foreign('scheduled_exam_id')->references('id')->on('exam_schedules')->nullOnDelete();
        });

        Schema::create('special_exam_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('exam_result_id');
            $table->text('reason');
            $table->json('supporting_docs')->nullable();
            $table->string('status', 50)->default('pending'); // pending, approved, rejected
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->dateTime('reviewed_at')->nullable();
            $table->unsignedBigInteger('scheduled_exam_id')->nullable();
            $table->dateTime('created_at')->useCurrent();
            $table->foreign('student_id')->references('id')->on('students')->restrictOnDelete();
            $table->foreign('exam_result_id')->references('id')->on('exam_results')->restrictOnDelete();
            $table->foreign('reviewed_by')->references('id')->on('staff')->nullOnDelete();
            $table->foreign('scheduled_exam_id')->references('id')->on('exam_schedules')->nullOnDelete();
        });

        Schema::create('remark_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('exam_result_id');
            $table->text('reason');
            $table->decimal('fee_amount', 12, 2);
            $table->tinyInteger('fee_paid')->default(0);
            $table->string('status', 50)->default('pending_fee'); // pending_fee, assigned, in_progress, completed
            $table->unsignedBigInteger('assigned_examiner_id')->nullable();
            $table->string('original_script_path', 500)->nullable();
            $table->decimal('new_marks', 5, 2)->nullable();
            $table->decimal('original_marks', 5, 2)->nullable();
            $table->string('outcome', 50)->nullable(); // grade_unchanged, grade_increased, grade_decreased
            $table->dateTime('outcome_at')->nullable();
            $table->dateTime('created_at')->useCurrent();
            $table->foreign('student_id')->references('id')->on('students')->restrictOnDelete();
            $table->foreign('exam_result_id')->references('id')->on('exam_results')->restrictOnDelete();
            $table->foreign('assigned_examiner_id')->references('id')->on('staff')->nullOnDelete();
        });

        Schema::create('grade_records', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('unit_id');
            $table->unsignedBigInteger('semester_id');
            $table->unsignedBigInteger('nursing_block_id')->nullable();
            $table->unsignedBigInteger('exam_result_id')->nullable();
            $table->decimal('final_score', 5, 2);
            $table->string('grade_letter', 5);
            $table->decimal('grade_points', 5, 2);
            $table->decimal('credit_hours', 5, 2)->default(0);
            $table->tinyInteger('is_repeat')->default(0);
            $table->tinyInteger('is_supplementary_pass')->default(0);
            $table->decimal('gpa_at_time', 5, 2)->nullable();
            $table->dateTime('recorded_at');
            $table->dateTime('created_at')->useCurrent();
            $table->unique(['student_id', 'unit_id', 'semester_id'], 'grade_rec_unique');
            $table->foreign('student_id')->references('id')->on('students')->restrictOnDelete();
            $table->foreign('unit_id')->references('id')->on('units')->restrictOnDelete();
            $table->foreign('semester_id')->references('id')->on('semesters')->restrictOnDelete();
            $table->foreign('nursing_block_id')->references('id')->on('nursing_blocks')->nullOnDelete();
            $table->foreign('exam_result_id')->references('id')->on('exam_results')->nullOnDelete();
        });

        Schema::create('examination_papers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('unit_id');
            $table->unsignedBigInteger('semester_id');
            $table->string('exam_type', 50); // main, supplementary, special
            $table->string('version', 10)->default('A'); // A, B
            $table->string('draft_file_path', 500)->nullable();
            $table->string('moderated_file_path', 500)->nullable();
            $table->string('approved_file_path', 500)->nullable();
            $table->tinyInteger('is_encrypted')->default(0);
            $table->string('encryption_key_hash', 255)->nullable();
            $table->string('status', 50)->default('draft'); // draft, tabled, moderated, approved, printed
            $table->unsignedBigInteger('prepared_by');
            $table->dateTime('tabled_at')->nullable();
            $table->dateTime('moderated_at')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->dateTime('approved_at')->nullable();
            $table->dateTime('created_at')->useCurrent();
            $table->foreign('unit_id')->references('id')->on('units')->restrictOnDelete();
            $table->foreign('semester_id')->references('id')->on('semesters')->restrictOnDelete();
            $table->foreign('prepared_by')->references('id')->on('staff')->restrictOnDelete();
            $table->foreign('approved_by')->references('id')->on('staff')->nullOnDelete();
        });

        Schema::create('nursing_block_progress', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('block_id');
            $table->unsignedBigInteger('program_id');
            $table->date('start_date');
            $table->date('expected_end_date')->nullable();
            $table->date('actual_end_date')->nullable();
            $table->tinyInteger('clinical_field_logs_submitted')->default(0);
            $table->tinyInteger('skills_lab_assessment_passed')->default(0);
            $table->decimal('theory_block_exam_score', 5, 2)->nullable();
            $table->decimal('clinical_exam_score', 5, 2)->nullable();
            $table->tinyInteger('is_block_passed')->default(0);
            $table->tinyInteger('is_progression_locked')->default(0);
            $table->string('block_status', 50)->default('in_progress'); // in_progress, completed, failed, repeat
            $table->dateTime('completed_at')->nullable();
            $table->dateTime('created_at')->useCurrent();
            $table->unique(['student_id', 'block_id']);
            $table->foreign('student_id')->references('id')->on('students')->restrictOnDelete();
            $table->foreign('block_id')->references('id')->on('nursing_blocks')->restrictOnDelete();
            $table->foreign('program_id')->references('id')->on('academic_programs')->restrictOnDelete();
        });

        Schema::create('competency_checklists', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('unit_id');
            $table->string('competency_code', 50);
            $table->string('competency_name', 300);
            $table->text('description')->nullable();
            $table->unsignedBigInteger('mentor_id')->nullable();
            $table->unsignedBigInteger('sub_county_hub_id')->nullable();
            $table->dateTime('created_at')->useCurrent();
            $table->foreign('unit_id')->references('id')->on('units')->restrictOnDelete();
            $table->foreign('mentor_id')->references('id')->on('staff')->nullOnDelete();
            $table->foreign('sub_county_hub_id')->references('id')->on('campuses')->nullOnDelete();
        });

        Schema::create('competency_assessments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('competency_checklist_id');
            $table->tinyInteger('is_competent')->default(0);
            $table->date('assessment_date');
            $table->unsignedBigInteger('assessed_by');
            $table->json('evidence_file_paths')->nullable();
            $table->text('notes')->nullable();
            $table->dateTime('created_at')->useCurrent();
            $table->foreign('student_id')->references('id')->on('students')->restrictOnDelete();
            $table->foreign('competency_checklist_id')->references('id')->on('competency_checklists')->restrictOnDelete();
            $table->foreign('assessed_by')->references('id')->on('staff')->restrictOnDelete();
            $table->index('student_id');
        });

        Schema::create('timetable_entries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('unit_allocation_id');
            $table->unsignedBigInteger('semester_id');
            $table->integer('day_of_week'); // 1=Monday..7=Sunday
            $table->time('start_time');
            $table->time('end_time');
            $table->string('venue', 200)->nullable();
            $table->string('timetable_type', 50)->default('class'); // class, practical, clinical, virtual
            $table->string('class_group', 100)->nullable();
            $table->tinyInteger('is_active')->default(1);
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->nullable()->useCurrentOnUpdate();
            $table->foreign('unit_allocation_id')->references('id')->on('unit_allocations')->restrictOnDelete();
            $table->foreign('semester_id')->references('id')->on('semesters')->restrictOnDelete();
        });

        // Deferred FKs from earlier migrations
        Schema::table('departments', function (Blueprint $table) {
            $table->foreign('hod_id')->references('id')->on('staff')->nullOnDelete();
        });

        Schema::table('academic_programs', function (Blueprint $table) {
            $table->foreign('approved_by_ceo_id')->references('id')->on('staff')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('academic_programs', function (Blueprint $table) {
            $table->dropForeign(['approved_by_ceo_id']);
        });

        Schema::table('departments', function (Blueprint $table) {
            $table->dropForeign(['hod_id']);
        });

        Schema::dropIfExists('timetable_entries');
        Schema::dropIfExists('competency_assessments');
        Schema::dropIfExists('competency_checklists');
        Schema::dropIfExists('nursing_block_progress');
        Schema::dropIfExists('examination_papers');
        Schema::dropIfExists('grade_records');
        Schema::dropIfExists('remark_requests');
        Schema::dropIfExists('special_exam_requests');
        Schema::dropIfExists('supplementary_requests');
        Schema::dropIfExists('exam_results');
        Schema::dropIfExists('exam_schedules');
        Schema::dropIfExists('exam_cards');
        Schema::dropIfExists('exam_eligibility_matrix');
        Schema::dropIfExists('cat_scores');
        Schema::dropIfExists('waitlist_entries');
        Schema::dropIfExists('deferral_requests');
        Schema::dropIfExists('registered_units');
        Schema::dropIfExists('student_semester_registrations');
        Schema::dropIfExists('attendance_summaries');
        Schema::dropIfExists('attendance_records');
        Schema::dropIfExists('attendance_sessions');
        Schema::dropIfExists('lesson_plan_approvals');
        Schema::dropIfExists('lesson_plans');
        Schema::dropIfExists('unit_allocations');
        Schema::dropIfExists('program_units');
    }
};
