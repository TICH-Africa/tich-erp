<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pension_schemes', function (Blueprint $table) {
            $table->id();
            $table->string('scheme_code', 20)->unique();
            $table->string('scheme_name', 300);
            $table->string('scheme_type', 50); // defined_benefit, defined_contribution, provident_fund, other
            $table->decimal('employer_contribution_pct', 5, 2)->default(0.00);
            $table->decimal('employee_contribution_pct', 5, 2)->default(0.00);
            $table->tinyInteger('is_active')->default(1);
            $table->dateTime('created_at')->useCurrent();
        });

        Schema::create('staff_bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('staff_id');
            $table->string('account_name', 300);
            $table->string('account_number', 50);
            $table->string('bank_name', 200);
            $table->string('bank_branch', 200)->nullable();
            $table->string('bank_code', 20);
            $table->tinyInteger('is_primary')->default(1);
            $table->tinyInteger('is_active')->default(1);
            $table->dateTime('created_at')->useCurrent();
            $table->unique(['staff_id', 'bank_code', 'account_number']);
            $table->foreign('staff_id')->references('id')->on('staff')->restrictOnDelete();
        });

        Schema::table('staff', function (Blueprint $table) {
            $table->foreign('pension_scheme_id')->references('id')->on('pension_schemes')->nullOnDelete();
            $table->foreign('bank_id')->references('id')->on('staff_bank_accounts')->nullOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('staff_contracts', function (Blueprint $table) {
            $table->id();
            $table->string('contract_number', 50)->unique();
            $table->unsignedBigInteger('staff_id');
            $table->string('contract_type', 50); // permanent, fixed_term, probation, internship, consultancy
            $table->string('job_title', 200);
            $table->unsignedBigInteger('department_id');
            $table->decimal('gross_salary', 12, 2);
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->tinyInteger('is_renewable')->default(0);
            $table->tinyInteger('renewal_notice_sent')->default(0);
            $table->date('renewal_notice_date')->nullable();
            $table->string('renewal_status', 50)->default('pending'); // pending, renewed, terminated, expired
            $table->unsignedBigInteger('new_contract_id')->nullable();
            $table->date('probation_end_date')->nullable();
            $table->string('probation_status', 50)->default('not_applicable'); // not_applicable, active, passed, failed
            $table->string('contract_document_path', 500)->nullable();
            $table->tinyInteger('is_signed')->default(0);
            $table->date('signed_date')->nullable();
            $table->string('witnessed_by', 200)->nullable();
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->nullable()->useCurrentOnUpdate();
            $table->foreign('staff_id')->references('id')->on('staff')->restrictOnDelete();
            $table->foreign('department_id')->references('id')->on('departments')->restrictOnDelete();
            $table->index('staff_id');
            $table->index('end_date');
        });

        Schema::table('staff_contracts', function (Blueprint $table) {
            $table->foreign('new_contract_id')->references('id')->on('staff_contracts')->nullOnDelete();
        });

        Schema::create('leave_types', function (Blueprint $table) {
            $table->id();
            $table->string('leave_code', 20)->unique();
            $table->string('leave_name', 200);
            $table->integer('days_allowed_per_year');
            $table->tinyInteger('is_payable')->default(1);
            $table->tinyInteger('requires_medical_certificate')->default(0);
            $table->tinyInteger('requires_approval_hod')->default(1);
            $table->tinyInteger('requires_approval_hr')->default(1);
            $table->string('gender_restriction', 50)->default('any'); // any, female_only, male_only
            $table->integer('min_service_months')->default(0);
            $table->integer('carry_forward_days')->default(0);
            $table->tinyInteger('is_active')->default(1);
            $table->dateTime('created_at')->useCurrent();
        });

        Schema::create('leave_balances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('staff_id');
            $table->unsignedBigInteger('leave_type_id');
            $table->integer('year');
            $table->integer('entitled_days');
            $table->integer('days_taken')->default(0);
            $table->integer('days_pending')->default(0);
            $table->decimal('balance_days', 5, 2)->default(0.00);
            $table->date('last_updated');
            $table->dateTime('created_at')->useCurrent();
            $table->unique(['staff_id', 'leave_type_id', 'year']);
            $table->foreign('staff_id')->references('id')->on('staff')->restrictOnDelete();
            $table->foreign('leave_type_id')->references('id')->on('leave_types')->restrictOnDelete();
        });

        Schema::create('leave_requests', function (Blueprint $table) {
            $table->id();
            $table->string('leave_number', 50)->unique();
            $table->unsignedBigInteger('staff_id');
            $table->unsignedBigInteger('leave_type_id');
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('days_requested', 5, 2);
            $table->text('reason');
            $table->tinyInteger('is_emergency')->default(0);
            $table->string('medical_certificate_path', 500)->nullable();
            $table->string('hod_approval_status', 50)->default('pending');
            $table->unsignedBigInteger('hod_approved_by')->nullable();
            $table->dateTime('hod_approved_at')->nullable();
            $table->string('hr_approval_status', 50)->default('pending');
            $table->unsignedBigInteger('hr_approved_by')->nullable();
            $table->dateTime('hr_approved_at')->nullable();
            $table->string('overall_status', 50)->default('pending_hod'); // pending_hod, pending_hr, approved, rejected, cancelled
            $table->tinyInteger('is_cancelled')->default(0);
            $table->text('cancellation_reason')->nullable();
            $table->date('return_date')->nullable();
            $table->tinyInteger('is_completed')->default(0);
            $table->text('handover_notes')->nullable();
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->nullable()->useCurrentOnUpdate();
            $table->foreign('staff_id')->references('id')->on('staff')->restrictOnDelete();
            $table->foreign('leave_type_id')->references('id')->on('leave_types')->restrictOnDelete();
            $table->foreign('hod_approved_by')->references('id')->on('staff')->nullOnDelete();
            $table->foreign('hr_approved_by')->references('id')->on('staff')->nullOnDelete();
            $table->index('staff_id');
            $table->index('overall_status');
        });

        Schema::create('staff_attendance', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('staff_id');
            $table->date('attendance_date');
            $table->time('clock_in_time')->nullable();
            $table->time('clock_out_time')->nullable();
            $table->decimal('work_hours', 5, 2)->nullable();
            $table->tinyInteger('is_present')->default(0);
            $table->tinyInteger('is_leave_day')->default(0);
            $table->unsignedBigInteger('leave_request_id')->nullable();
            $table->tinyInteger('is_off_campus')->default(0);
            $table->string('field_project_name', 300)->nullable();
            $table->string('location_lat_long', 100)->nullable();
            $table->string('notes', 500)->nullable();
            $table->unsignedBigInteger('recorded_by')->nullable();
            $table->dateTime('created_at')->useCurrent();
            $table->unique(['staff_id', 'attendance_date']);
            $table->foreign('staff_id')->references('id')->on('staff')->restrictOnDelete();
            $table->foreign('leave_request_id')->references('id')->on('leave_requests')->nullOnDelete();
            $table->foreign('recorded_by')->references('id')->on('staff')->nullOnDelete();
        });

        Schema::create('staff_attendance_summary', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('staff_id');
            $table->integer('month');
            $table->integer('year');
            $table->integer('total_work_days');
            $table->integer('days_present')->default(0);
            $table->integer('days_absent')->default(0);
            $table->integer('days_on_leave')->default(0);
            $table->integer('late_arrivals')->default(0);
            $table->integer('early_departures')->default(0);
            $table->integer('off_campus_days')->default(0);
            $table->decimal('overtime_hours', 6, 2)->default(0.00);
            $table->decimal('attendance_percentage', 5, 2)->default(0.00);
            $table->dateTime('last_calculated_at');
            $table->dateTime('created_at')->useCurrent();
            $table->unique(['staff_id', 'month', 'year']);
            $table->foreign('staff_id')->references('id')->on('staff')->restrictOnDelete();
        });

        Schema::create('performance_reviews', function (Blueprint $table) {
            $table->id();
            $table->string('review_number', 50)->unique();
            $table->unsignedBigInteger('staff_id');
            $table->unsignedBigInteger('reviewer_id');
            $table->date('review_period_start');
            $table->date('review_period_end');
            $table->date('review_date');
            $table->json('kpi_scores')->nullable();
            $table->string('overall_rating', 50); // exceptional, meets_exceeds, meets_expectations, needs_improvement, unsatisfactory
            $table->text('strengths')->nullable();
            $table->text('development_areas')->nullable();
            $table->text('training_recommendations')->nullable();
            $table->text('goals_for_next_period')->nullable();
            $table->text('staff_comments')->nullable();
            $table->tinyInteger('staff_agrees')->default(0);
            $table->dateTime('staff_signed_at')->nullable();
            $table->dateTime('reviewer_signed_at')->nullable();
            $table->dateTime('hr_approved_at')->nullable();
            $table->dateTime('created_at')->useCurrent();
            $table->foreign('staff_id')->references('id')->on('staff')->restrictOnDelete();
            $table->foreign('reviewer_id')->references('id')->on('staff')->restrictOnDelete();
            $table->index('staff_id');
            $table->index('reviewer_id');
        });

        Schema::create('staff_disciplinary_cases', function (Blueprint $table) {
            $table->id();
            $table->string('case_number', 50)->unique();
            $table->unsignedBigInteger('staff_id');
            $table->date('incident_date');
            $table->text('incident_description');
            $table->string('violation_type', 50); // misconduct, gross_misconduct, absenteeism, policy_breach, performance, other
            $table->unsignedBigInteger('reported_by');
            $table->unsignedBigInteger('assigned_investigator_id')->nullable();
            $table->string('case_status', 50)->default('open'); // open, under_investigation, hearing_scheduled, hearing_held, resolved, closed
            $table->dateTime('hearing_date')->nullable();
            $table->text('hearing_outcome')->nullable();
            $table->string('sanction_type', 50)->nullable();
            $table->date('sanction_start_date')->nullable();
            $table->date('sanction_end_date')->nullable();
            $table->text('staff_statement')->nullable();
            $table->json('witness_statements')->nullable();
            $table->date('decision_date')->nullable();
            $table->unsignedBigInteger('decision_by')->nullable();
            $table->tinyInteger('is_appeal')->default(0);
            $table->string('appeal_outcome', 50)->nullable();
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->nullable()->useCurrentOnUpdate();
            $table->foreign('staff_id')->references('id')->on('staff')->restrictOnDelete();
            $table->foreign('reported_by')->references('id')->on('staff')->restrictOnDelete();
            $table->foreign('assigned_investigator_id')->references('id')->on('staff')->nullOnDelete();
            $table->foreign('decision_by')->references('id')->on('staff')->nullOnDelete();
        });

        Schema::create('staff_qualifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('staff_id');
            $table->string('qualification_type', 50); // certificate, diploma, degree, masters, phd, professional_cert, trade_test
            $table->string('qualification_name', 300);
            $table->string('institution', 300);
            $table->string('country', 100)->default('Kenya');
            $table->integer('year_completed');
            $table->string('grade_or_class', 50)->nullable();
            $table->string('certificate_number', 50)->nullable();
            $table->string('document_path', 500)->nullable();
            $table->tinyInteger('is_verified')->default(0);
            $table->unsignedBigInteger('verified_by')->nullable();
            $table->dateTime('verified_at')->nullable();
            $table->dateTime('created_at')->useCurrent();
            $table->foreign('staff_id')->references('id')->on('staff')->restrictOnDelete();
            $table->foreign('verified_by')->references('id')->on('staff')->nullOnDelete();
        });

        Schema::create('staff_professional_licenses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('staff_id');
            $table->string('license_type', 50); // nursing_license, clinical_officer, teacher_registration, practicing_license, trade_license, other
            $table->string('issuing_body', 300);
            $table->string('license_number', 100);
            $table->date('issue_date');
            $table->date('expiry_date');
            $table->tinyInteger('is_expired')->default(0);
            $table->integer('days_to_expiry')->nullable();
            $table->tinyInteger('alert_sent_30_days')->default(0);
            $table->tinyInteger('alert_sent_60_days')->default(0);
            $table->string('document_path', 500)->nullable();
            $table->tinyInteger('is_verified')->default(0);
            $table->unsignedBigInteger('verified_by')->nullable();
            $table->dateTime('verified_at')->nullable();
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->nullable()->useCurrentOnUpdate();
            $table->foreign('staff_id')->references('id')->on('staff')->restrictOnDelete();
            $table->foreign('verified_by')->references('id')->on('staff')->nullOnDelete();
            $table->index('expiry_date');
        });

        Schema::create('professional_development', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('staff_id');
            $table->string('activity_type', 50); // training, workshop, conference, seminar, cpd, study_leave, attachment, mentorship
            $table->string('activity_name', 300);
            $table->string('organizer', 300)->nullable();
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('hours_or_days', 6, 2);
            $table->decimal('cpd_credits_earned', 5, 2)->default(0.00);
            $table->string('location', 300)->nullable();
            $table->tinyInteger('is_external')->default(0);
            $table->decimal('cost', 12, 2)->nullable();
            $table->string('funded_by', 50)->nullable(); // institution, self, donor, sponsor
            $table->string('certificate_path', 500)->nullable();
            $table->tinyInteger('is_completed')->default(0);
            $table->string('appraisal_relevance', 500)->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->dateTime('approved_at')->nullable();
            $table->dateTime('created_at')->useCurrent();
            $table->foreign('staff_id')->references('id')->on('staff')->restrictOnDelete();
            $table->foreign('approved_by')->references('id')->on('staff')->nullOnDelete();
        });

        Schema::create('job_vacancies', function (Blueprint $table) {
            $table->id();
            $table->string('vacancy_number', 50)->unique();
            $table->string('job_title', 200);
            $table->unsignedBigInteger('department_id');
            $table->string('employment_type', 50); // permanent, contract, intern, visiting, casual
            $table->string('position_grade', 20)->nullable();
            $table->integer('slots_available')->default(1);
            $table->text('job_description');
            $table->text('requirements');
            $table->text('responsibilities');
            $table->string('salary_scale', 200)->nullable();
            $table->text('benefits')->nullable();
            $table->string('min_qualification', 50);
            $table->date('closing_date');
            $table->tinyInteger('is_published')->default(0);
            $table->date('published_on')->nullable();
            $table->tinyInteger('is_closed')->default(0);
            $table->tinyInteger('closes_automatically')->default(1);
            $table->integer('slots_filled')->default(0);
            $table->unsignedBigInteger('created_by');
            $table->dateTime('created_at')->useCurrent();
            $table->foreign('department_id')->references('id')->on('departments')->restrictOnDelete();
            $table->foreign('created_by')->references('id')->on('staff')->restrictOnDelete();
        });

        Schema::create('recruitment_applications', function (Blueprint $table) {
            $table->id();
            $table->string('application_number', 50)->unique();
            $table->unsignedBigInteger('vacancy_id');
            $table->string('full_name', 300);
            $table->string('email', 255);
            $table->string('phone_number', 30);
            $table->string('postal_address', 300)->nullable();
            $table->string('highest_qualification', 50);
            $table->string('current_organization', 300)->nullable();
            $table->string('area_of_specialization', 300)->nullable();
            $table->integer('years_of_experience')->default(0);
            $table->string('cv_file_path', 500);
            $table->string('cover_letter_file_path', 500)->nullable();
            $table->json('certificates_file_paths')->nullable();
            $table->tinyInteger('is_shortlisted')->default(0);
            $table->string('shortlist_status', 50)->default('pending'); // pending, shortlisted, rejected
            $table->dateTime('interview_date')->nullable();
            $table->json('interview_panel_ids')->nullable();
            $table->decimal('interview_score', 5, 2)->nullable();
            $table->text('interview_notes')->nullable();
            $table->tinyInteger('offer_made')->default(0);
            $table->tinyInteger('offer_accepted')->default(0);
            $table->unsignedBigInteger('new_staff_id')->nullable();
            $table->tinyInteger('is_onboarded')->default(0);
            $table->text('rejection_reason')->nullable();
            $table->string('application_source', 50)->default('portal'); // portal, referral, walk_in
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->nullable()->useCurrentOnUpdate();
            $table->foreign('vacancy_id')->references('id')->on('job_vacancies')->restrictOnDelete();
            $table->foreign('new_staff_id')->references('id')->on('staff')->nullOnDelete();
            $table->index('vacancy_id');
            $table->index('shortlist_status');
        });

Schema::create('policy_acknowledgements', function (Blueprint $table) {
            $table->id();
            $table->string('policy_name', 200);
            $table->string('policy_version', 20);
            $table->string('policy_file_path', 500);
            $table->date('effective_date');
            $table->unsignedBigInteger('staff_id');
            $table->tinyInteger('is_acknowledged')->default(0);
            $table->dateTime('acknowledged_at')->nullable();
            $table->string('acknowledgement_method', 50)->default('digital'); // digital, physical
            $table->dateTime('created_at')->useCurrent();
            $table->unique(['staff_id', 'policy_name', 'policy_version'], 'policy_ack_unique');
            $table->foreign('staff_id')->references('id')->on('staff')->restrictOnDelete();
        });

        // Deferred FKs from earlier migrations
        Schema::table('users', function (Blueprint $table) {
            $table->foreign('staff_id')->references('id')->on('staff')->nullOnDelete();
            $table->foreign('student_id')->references('id')->on('students')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });

        Schema::table('user_roles', function (Blueprint $table) {
            $table->foreign('campus_id')->references('id')->on('campuses')->nullOnDelete();
            $table->foreign('department_id')->references('id')->on('departments')->nullOnDelete();
            $table->foreign('assigned_by')->references('id')->on('users')->nullOnDelete();
        });

        Schema::table('students', function (Blueprint $table) {
            $table->foreign('admission_letter_id')->references('id')->on('admission_letters')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropForeign(['admission_letter_id']);
        });

        Schema::table('user_roles', function (Blueprint $table) {
            $table->dropForeign(['campus_id']);
            $table->dropForeign(['department_id']);
            $table->dropForeign(['assigned_by']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['staff_id']);
            $table->dropForeign(['student_id']);Those curriculum tools are still in the codebase; the sidebar hides most of them when a programme has no intake
            $table->dropForeign(['created_by']);
        });

        Schema::dropIfExists('policy_acknowledgements');
        Schema::dropIfExists('recruitment_applications');
        Schema::dropIfExists('job_vacancies');
        Schema::dropIfExists('professional_development');
        Schema::dropIfExists('staff_professional_licenses');
        Schema::dropIfExists('staff_qualifications');
        Schema::dropIfExists('staff_disciplinary_cases');
        Schema::dropIfExists('performance_reviews');
        Schema::dropIfExists('staff_attendance_summary');
        Schema::dropIfExists('staff_attendance');
        Schema::dropIfExists('leave_requests');
        Schema::dropIfExists('leave_balances');
        Schema::dropIfExists('leave_types');

        Schema::table('staff_contracts', function (Blueprint $table) {
            $table->dropForeign(['new_contract_id']);
        });

        Schema::dropIfExists('staff_contracts');

        Schema::table('staff', function (Blueprint $table) {
            $table->dropForeign(['pension_scheme_id']);
            $table->dropForeign(['bank_id']);
            $table->dropForeign(['user_id']);
        });

        Schema::dropIfExists('staff_bank_accounts');
        Schema::dropIfExists('pension_schemes');
    }
};
