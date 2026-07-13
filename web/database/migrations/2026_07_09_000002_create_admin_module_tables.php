<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Minimal staff skeleton — full definition in HR migration
        // Created here because academic_programs, departments, applicants all FK → staff
        Schema::create('staff', function (Blueprint $table) {
            $table->id();
            $table->string('employee_number', 50)->unique();
            $table->string('title', 100)->nullable();
            $table->string('first_name', 100);
            $table->string('middle_name', 100)->nullable();
            $table->string('surname', 100);
            $table->date('date_of_birth');
            $table->string('gender', 20);
            $table->string('national_id_number', 50)->nullable();
            $table->string('passport_number', 50)->nullable();
            $table->string('nationality', 100)->default('Kenyan');
            $table->string('email', 255)->unique();
            $table->string('phone_number', 30);
            $table->string('alt_phone_number', 30)->nullable();
            $table->string('postal_address', 300)->nullable();
            $table->string('physical_address', 500)->nullable();
            $table->string('emergency_contact_name', 300)->nullable();
            $table->string('emergency_contact_phone', 30)->nullable();
            $table->string('emergency_contact_relationship', 50)->nullable();
            $table->string('photo_path', 500)->nullable();
            $table->unsignedBigInteger('department_id');
            $table->string('job_title', 200);
            $table->string('job_grade', 20)->nullable();
            $table->string('employment_category', 50); // permanent, contract, intern, visiting, casual
            $table->date('employment_start_date');
            $table->date('contract_end_date')->nullable();
            $table->tinyInteger('is_on_probation')->default(0);
            $table->date('probation_end_date')->nullable();
            $table->decimal('gross_monthly_salary', 12, 2)->default(0.00);
            $table->unsignedBigInteger('bank_id')->nullable();
            $table->string('kra_pin', 50)->nullable();
            $table->string('nssf_number', 50)->nullable();
            $table->string('sha_number', 50)->nullable();
            $table->unsignedBigInteger('pension_scheme_id')->nullable();
            $table->string('employment_status', 50)->default('active'); // active, on_leave, suspended, terminated, resigned
            $table->date('exit_date')->nullable();
            $table->string('exit_reason', 500)->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->tinyInteger('is_teaching_staff')->default(0);
            $table->tinyInteger('is_nursing_license_required')->default(0);
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->nullable()->useCurrentOnUpdate();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->foreign('department_id')->references('id')->on('departments')->restrictOnDelete();
            $table->index('department_id');
            $table->index('employment_status');
            $table->index('national_id_number');
        });

        Schema::create('rpl_applications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('applicant_id');
            $table->unsignedBigInteger('program_id');
            $table->string('prior_experience_type', 50); // practical_trade, work_history, chp_status, informal_training
            $table->integer('prior_experience_years')->nullable();
            $table->json('supporting_documents')->nullable();
            $table->decimal('trade_equivalence_score', 5, 2)->nullable();
            $table->json('credit_exemption_unit_ids')->nullable();
            $table->string('status', 50)->default('pending_assessment');
            $table->unsignedBigInteger('assessed_by')->nullable();
            $table->dateTime('assessed_at')->nullable();
            $table->integer('total_credits_awarded')->default(0);
            $table->dateTime('created_at')->useCurrent();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->foreign('program_id')->references('id')->on('academic_programs')->restrictOnDelete();
        });

        Schema::create('applicants', function (Blueprint $table) {
            $table->id();
            $table->string('application_number', 50)->unique();
            $table->unsignedBigInteger('program_id');
            $table->unsignedBigInteger('preferred_campus_id')->nullable();
            $table->string('first_name', 100);
            $table->string('surname', 100);
            $table->date('date_of_birth');
            $table->string('gender', 20);
            $table->string('national_id_number', 50)->nullable();
            $table->string('passport_number', 50)->nullable();
            $table->string('email', 255);
            $table->string('phone_number', 30);
            $table->string('home_county', 100)->nullable();
            $table->string('entry_qualification', 50)->nullable(); // kcse, class8, certificate, diploma, rpl
            $table->tinyInteger('application_fee_paid')->default(0);
            $table->string('application_fee_payment_ref', 100)->nullable();
            $table->dateTime('application_fee_paid_at')->nullable();
            $table->unsignedBigInteger('rpl_application_id')->nullable();
            $table->string('status', 50)->default('submitted'); // submitted, academic_review, fee_pending, paid, admitted, rejected
            $table->string('academic_review_status', 50)->default('pending');
            $table->unsignedBigInteger('academic_reviewer_id')->nullable();
            $table->string('application_source', 50)->default('online'); // online, field_agent, paper
            $table->tinyInteger('is_offline_cached')->default(0);
            $table->string('offline_sync_id', 100)->nullable();
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->nullable()->useCurrentOnUpdate();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->foreign('program_id')->references('id')->on('academic_programs')->restrictOnDelete();
            $table->foreign('preferred_campus_id')->references('id')->on('campuses')->nullOnDelete();
            $table->foreign('rpl_application_id')->references('id')->on('rpl_applications')->nullOnDelete();
            $table->foreign('academic_reviewer_id')->references('id')->on('staff')->nullOnDelete();
        });

        // Add FK from rpl_applications back to applicants
        Schema::table('rpl_applications', function (Blueprint $table) {
            $table->foreign('applicant_id')->references('id')->on('applicants')->restrictOnDelete();
            $table->foreign('assessed_by')->references('id')->on('staff')->nullOnDelete();
        });

        Schema::create('admission_letters', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_id');
            $table->string('letter_number', 50)->unique();
            $table->date('issue_date');
            $table->date('reporting_date');
            $table->unsignedBigInteger('enrollment_campus_id');
            $table->unsignedBigInteger('generated_by');
            $table->tinyInteger('is_printed')->default(0);
            $table->tinyInteger('is_dispatched')->default(0);
            $table->dateTime('dispatched_at')->nullable();
            $table->dateTime('created_at')->useCurrent();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->foreign('enrollment_campus_id')->references('id')->on('campuses')->restrictOnDelete();
            $table->foreign('generated_by')->references('id')->on('staff')->restrictOnDelete();
        });

        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->string('registration_number', 50)->unique();
            $table->unsignedBigInteger('application_id');
            $table->unsignedBigInteger('program_id');
            $table->string('cohort_intake', 20);
            $table->unsignedBigInteger('enrollment_campus_id');
            $table->unsignedBigInteger('current_semester_id')->nullable();
            $table->unsignedBigInteger('current_nursing_block_id')->nullable();
            $table->string('enrollment_status', 50)->default('pending'); // pending, active, deferred, suspended, withdrawn, graduated, alumni
            $table->string('entry_pathway', 50)->default('regular'); // regular, rpl, credit_transfer
            $table->unsignedBigInteger('admission_letter_id')->nullable();
            $table->string('photo_path', 500)->nullable();
            $table->date('date_of_admission');
            $table->tinyInteger('is_nursing_student')->default(0);
            $table->string('kcse_english_grade', 5)->nullable();
            $table->string('kcse_biology_grade', 5)->nullable();
            $table->string('kcse_science_grade', 5)->nullable();
            $table->string('fee_clearance_status', 50)->default('pending'); // pending, partial, cleared
            $table->decimal('overall_balance', 12, 2)->default(0.00);
            $table->unsignedBigInteger('user_id')->nullable();
            $table->tinyInteger('is_hostel_seeker')->default(0);
            $table->unsignedBigInteger('hostel_allocation_id')->nullable();
            $table->string('emergency_contact_name', 300)->nullable();
            $table->string('emergency_contact_phone', 30)->nullable();
            $table->string('emergency_contact_relationship', 50)->nullable();
            $table->tinyInteger('is_active')->default(1);
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->nullable()->useCurrentOnUpdate();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->foreign('application_id')->references('id')->on('applicants')->restrictOnDelete();
            $table->foreign('program_id')->references('id')->on('academic_programs')->restrictOnDelete();
            $table->foreign('enrollment_campus_id')->references('id')->on('campuses')->restrictOnDelete();
            $table->foreign('current_semester_id')->references('id')->on('semesters')->nullOnDelete();
            $table->foreign('current_nursing_block_id')->references('id')->on('nursing_blocks')->nullOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });

        // Add FK from admission_letters back to students
        Schema::table('admission_letters', function (Blueprint $table) {
            $table->foreign('student_id')->references('id')->on('students')->restrictOnDelete();
        });

        Schema::create('application_documents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('applicant_id');
            $table->string('document_type', 50); // passport_photo, kcse_slip, id_copy, birth_cert, fee_receipt, medical_form
            $table->string('file_path', 500);
            $table->string('original_filename', 255);
            $table->string('mime_type', 100);
            $table->tinyInteger('is_verified')->default(0);
            $table->unsignedBigInteger('verified_by')->nullable();
            $table->dateTime('verified_at')->nullable();
            $table->dateTime('created_at')->useCurrent();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->foreign('applicant_id')->references('id')->on('applicants')->restrictOnDelete();
            $table->foreign('verified_by')->references('id')->on('staff')->nullOnDelete();
        });

        Schema::create('student_addresses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_id');
            $table->string('address_type', 50); // permanent, postal, guardian
            $table->string('postal_address', 300)->nullable();
            $table->string('physical_address', 500)->nullable();
            $table->string('county', 100)->nullable();
            $table->string('sub_county', 100)->nullable();
            $table->string('phone', 30)->nullable();
            $table->dateTime('created_at')->useCurrent();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->foreign('student_id')->references('id')->on('students')->restrictOnDelete();
        });

        Schema::create('student_next_of_kin', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_id');
            $table->string('full_name', 300);
            $table->string('relationship', 100);
            $table->string('phone_number', 30);
            $table->string('alt_phone', 30)->nullable();
            $table->string('email', 255)->nullable();
            $table->string('physical_address', 500)->nullable();
            $table->string('occupation', 200)->nullable();
            $table->dateTime('created_at')->useCurrent();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->foreign('student_id')->references('id')->on('students')->restrictOnDelete();
        });

        Schema::create('medical_records', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_id');
            $table->string('blood_group', 10)->nullable();
            $table->text('known_medical_conditions')->nullable();
            $table->text('allergies')->nullable();
            $table->text('special_needs')->nullable();
            $table->string('disability_status', 50)->default('none'); // none, physical, visual, hearing, learning, other
            $table->text('disability_description')->nullable();
            $table->string('medical_form_file_path', 500)->nullable();
            $table->tinyInteger('is_approved')->default(0);
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->dateTime('reviewed_at')->nullable();
            $table->dateTime('created_at')->useCurrent();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->foreign('student_id')->references('id')->on('students')->restrictOnDelete();
            $table->foreign('reviewed_by')->references('id')->on('staff')->nullOnDelete();
        });

        Schema::create('disciplinary_records', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_id');
            $table->string('case_number', 50)->unique();
            $table->date('incident_date');
            $table->text('incident_description');
            $table->string('violation_type', 100);
            $table->string('severity', 50);
            $table->unsignedBigInteger('reported_by');
            $table->unsignedBigInteger('assigned_officer_id')->nullable();
            $table->string('case_status', 50)->default('open'); // open, under_investigation, hearing_scheduled, resolved, closed, appealed
            $table->dateTime('hearing_date')->nullable();
            $table->text('decision')->nullable();
            $table->string('sanction', 500)->nullable();
            $table->string('appeal_status', 50)->nullable();
            $table->text('appeal_notes')->nullable();
            $table->dateTime('resolved_at')->nullable();
            $table->dateTime('created_at')->useCurrent();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->foreign('student_id')->references('id')->on('students')->restrictOnDelete();
            $table->foreign('reported_by')->references('id')->on('staff')->restrictOnDelete();
            $table->foreign('assigned_officer_id')->references('id')->on('staff')->nullOnDelete();
        });

        Schema::create('sacco_members', function (Blueprint $table) {
            $table->id();
            $table->string('member_number', 50)->unique();
            $table->unsignedBigInteger('staff_id');
            $table->decimal('registration_fee', 12, 2)->default(500.00);
            $table->tinyInteger('registration_fee_paid')->default(0);
            $table->date('registration_fee_paid_date')->nullable();
            $table->string('membership_status', 50)->default('pending_fee'); // pending_fee, active, suspended, withdrawn
            $table->decimal('monthly_contribution_min', 12, 2)->default(200.00);
            $table->decimal('total_savings_balance', 12, 2)->default(0.00);
            $table->decimal('max_loan_eligibility', 12, 2)->default(0.00);
            $table->unsignedBigInteger('guarantor_1_id')->nullable();
            $table->unsignedBigInteger('guarantor_2_id')->nullable();
            $table->date('joining_date');
            $table->tinyInteger('is_active')->default(1);
            $table->dateTime('created_at')->useCurrent();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->foreign('staff_id')->references('id')->on('staff')->restrictOnDelete();
            $table->foreign('guarantor_1_id')->references('id')->on('sacco_members')->nullOnDelete();
            $table->foreign('guarantor_2_id')->references('id')->on('sacco_members')->nullOnDelete();
        });

        Schema::create('sacco_savings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('member_id');
            $table->string('transaction_type', 50); // monthly_contribution, voluntary_deposit, interest_credit, withdrawal, fee_deduction
            $table->decimal('amount', 12, 2);
            $table->date('transaction_date');
            $table->string('reference_number', 50)->unique();
            $table->string('notes', 500)->nullable();
            $table->unsignedBigInteger('processed_by');
            $table->dateTime('created_at')->useCurrent();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->foreign('member_id')->references('id')->on('sacco_members')->restrictOnDelete();
            $table->foreign('processed_by')->references('id')->on('staff')->restrictOnDelete();
        });

        Schema::create('sacco_loans', function (Blueprint $table) {
            $table->id();
            $table->string('loan_number', 50)->unique();
            $table->unsignedBigInteger('member_id');
            $table->string('loan_type', 50); // toolkit, emergency, asset_financing, school_fee, other
            $table->decimal('principal_amount', 12, 2);
            $table->decimal('interest_rate', 5, 2);
            $table->integer('loan_term_months');
            $table->decimal('monthly_repayment', 12, 2);
            $table->decimal('total_interest', 12, 2);
            $table->decimal('total_amount_due', 12, 2);
            $table->decimal('max_eligible_amount', 12, 2);
            $table->unsignedBigInteger('guarantor_1_id')->nullable();
            $table->unsignedBigInteger('guarantor_2_id')->nullable();
            $table->date('application_date');
            $table->string('application_status', 50)->default('pending'); // pending, under_review, approved, rejected, disbursed, closed, defaulted
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->date('approved_date')->nullable();
            $table->date('disbursement_date')->nullable();
            $table->decimal('outstanding_balance', 12, 2)->default(0.00);
            $table->string('default_status', 50)->default('none'); // none, 30_days, 60_days, 90_days
            $table->dateTime('created_at')->useCurrent();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->foreign('member_id')->references('id')->on('sacco_members')->restrictOnDelete();
            $table->foreign('guarantor_1_id')->references('id')->on('sacco_members')->nullOnDelete();
            $table->foreign('guarantor_2_id')->references('id')->on('sacco_members')->nullOnDelete();
            $table->foreign('approved_by')->references('id')->on('staff')->nullOnDelete();
        });

        Schema::create('sacco_loan_repayments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('loan_id');
            $table->integer('repayment_number');
            $table->date('due_date');
            $table->decimal('due_amount', 12, 2);
            $table->decimal('principal_portion', 12, 2);
            $table->decimal('interest_portion', 12, 2);
            $table->decimal('amount_paid', 12, 2)->default(0.00);
            $table->date('payment_date')->nullable();
            $table->string('payment_reference', 100)->nullable();
            $table->string('payment_method', 50)->nullable();
            $table->string('status', 50)->default('pending'); // pending, partial, paid, overdue
            $table->dateTime('created_at')->useCurrent();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->foreign('loan_id')->references('id')->on('sacco_loans')->restrictOnDelete();
        });

        Schema::create('cafeteria_staff_memberships', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('staff_id');
            $table->string('membership_status', 50)->default('active');
            $table->decimal('monthly_deduction', 12, 2)->default(0.00);
            $table->date('enrolled_at');
            $table->date('withdrawn_at')->nullable();
            $table->dateTime('created_at')->useCurrent();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->foreign('staff_id')->references('id')->on('staff')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cafeteria_staff_memberships');
        Schema::dropIfExists('sacco_loan_repayments');
        Schema::dropIfExists('sacco_loans');
        Schema::dropIfExists('sacco_savings');
        Schema::dropIfExists('sacco_members');
        Schema::dropIfExists('disciplinary_records');
        Schema::dropIfExists('medical_records');
        Schema::dropIfExists('student_next_of_kin');
        Schema::dropIfExists('student_addresses');
        Schema::dropIfExists('application_documents');
        Schema::dropIfExists('students');
        Schema::dropIfExists('admission_letters');
        Schema::dropIfExists('applicants');
        Schema::dropIfExists('rpl_applications');
        Schema::dropIfExists('staff');
    }
};
