<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('staff', function (Blueprint $table) {
            if (! Schema::hasColumn('staff', 'marital_status')) {
                $table->string('marital_status', 50)->nullable()->after('gender');
            }
            if (! Schema::hasColumn('staff', 'helb_number')) {
                $table->string('helb_number', 50)->nullable()->after('sha_number');
            }
            if (! Schema::hasColumn('staff', 'campus_id')) {
                $table->unsignedBigInteger('campus_id')->nullable()->after('department_id');
            }
            if (! Schema::hasColumn('staff', 'line_manager_id')) {
                $table->unsignedBigInteger('line_manager_id')->nullable()->after('campus_id');
            }
            if (! Schema::hasColumn('staff', 'salary_scale')) {
                $table->string('salary_scale', 50)->nullable()->after('job_grade');
            }
            if (! Schema::hasColumn('staff', 'incremental_date')) {
                $table->date('incremental_date')->nullable()->after('salary_scale');
            }
            if (! Schema::hasColumn('staff', 'confirmation_date')) {
                $table->date('confirmation_date')->nullable()->after('probation_end_date');
            }
            if (! Schema::hasColumn('staff', 'project_code')) {
                $table->string('project_code', 100)->nullable()->after('employment_category');
            }
            if (! Schema::hasColumn('staff', 'allowances_json')) {
                $table->json('allowances_json')->nullable()->after('gross_monthly_salary');
            }
            if (! Schema::hasColumn('staff', 'is_profile_locked')) {
                $table->tinyInteger('is_profile_locked')->default(0)->after('is_teaching_staff');
            }
            if (! Schema::hasColumn('staff', 'onboarding_completed_at')) {
                $table->dateTime('onboarding_completed_at')->nullable()->after('is_profile_locked');
            }
            if (! Schema::hasColumn('staff', 'home_county')) {
                $table->string('home_county', 100)->nullable()->after('nationality');
            }
            if (! Schema::hasColumn('staff', 'postal_code')) {
                $table->string('postal_code', 20)->nullable()->after('postal_address');
            }
        });

        Schema::create('staff_next_of_kin', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('staff_id');
            $table->string('full_name', 300);
            $table->string('relationship', 100);
            $table->string('phone_number', 30);
            $table->string('alt_phone', 30)->nullable();
            $table->string('email', 255)->nullable();
            $table->string('physical_address', 500)->nullable();
            $table->string('occupation', 200)->nullable();
            $table->tinyInteger('is_primary')->default(1);
            $table->dateTime('created_at')->useCurrent();
            $table->foreign('staff_id')->references('id')->on('staff')->restrictOnDelete();
            $table->index('staff_id');
        });

        Schema::create('staff_allowances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('staff_id');
            $table->string('allowance_type', 50); // housing, transport, lunch, acting, responsibility, medical, other
            $table->string('allowance_name', 200);
            $table->decimal('amount', 12, 2);
            $table->date('effective_date');
            $table->date('end_date')->nullable();
            $table->tinyInteger('is_active')->default(1);
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->dateTime('approved_at')->nullable();
            $table->dateTime('created_at')->useCurrent();
            $table->foreign('staff_id')->references('id')->on('staff')->restrictOnDelete();
            $table->foreign('approved_by')->references('id')->on('staff')->nullOnDelete();
            $table->index('staff_id');
        });

        Schema::create('staff_documents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('staff_id');
            $table->string('document_type', 50); // cv, academic_certificate, professional_license, kra_pin, nssf, sha, national_id, good_conduct, passport_photo, bank_confirmation, other
            $table->string('document_name', 300);
            $table->string('file_path', 500);
            $table->string('original_filename', 255);
            $table->string('mime_type', 100);
            $table->integer('file_size')->nullable();
            $table->date('issue_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->tinyInteger('is_verified')->default(0);
            $table->unsignedBigInteger('verified_by')->nullable();
            $table->dateTime('verified_at')->nullable();
            $table->string('version', 20)->default('1');
            $table->unsignedBigInteger('replaced_by_id')->nullable();
            $table->tinyInteger('is_missing')->default(0);
            $table->text('notes')->nullable();
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->nullable()->useCurrentOnUpdate();
            $table->foreign('staff_id')->references('id')->on('staff')->restrictOnDelete();
            $table->foreign('verified_by')->references('id')->on('staff')->nullOnDelete();
            $table->foreign('replaced_by_id')->references('id')->on('staff_documents')->nullOnDelete();
            $table->index(['staff_id', 'document_type']);
            $table->index('expiry_date');
        });

        Schema::create('staff_onboarding', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('staff_id')->nullable();
            $table->unsignedBigInteger('applicant_id')->nullable();
            $table->string('onboarding_number', 50)->unique();
            $table->string('current_step', 50)->default('biodata'); // biodata, employment_terms, banking, documents, contract, orientation, statutory, ess_account, completed
            $table->string('status', 50)->default('in_progress'); // in_progress, pending_hr_review, approved, rejected, completed
            $table->text('rejection_reason')->nullable();
            $table->json('completed_steps')->nullable();
            $table->json('missing_documents')->nullable();
            $table->tinyInteger('is_biodata_locked')->default(0);
            $table->unsignedBigInteger('locked_by')->nullable();
            $table->dateTime('locked_at')->nullable();
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->dateTime('reviewed_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->nullable()->useCurrentOnUpdate();
            $table->foreign('staff_id')->references('id')->on('staff')->nullOnDelete();
            $table->foreign('applicant_id')->references('id')->on('recruitment_applications')->nullOnDelete();
            $table->foreign('locked_by')->references('id')->on('staff')->nullOnDelete();
            $table->foreign('reviewed_by')->references('id')->on('staff')->nullOnDelete();
            $table->index('status');
            $table->index('current_step');
        });

        Schema::create('staff_status_history', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('staff_id');
            $table->string('change_type', 50); // promotion, transfer, acting, salary_review, sabbatical, study_leave, unpaid_leave, retirement, termination, resignation, redundancy, dismissal, confirmation, re_engagement, other
            $table->string('previous_status', 50)->nullable();
            $table->string('new_status', 50)->nullable();
            $table->text('reason')->nullable();
            $table->json('metadata')->nullable(); // stores additional context like old/new department, position, salary, etc.
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->string('approval_reference', 100)->nullable();
            $table->date('effective_date')->nullable();
            $table->dateTime('created_at')->useCurrent();
            $table->foreign('staff_id')->references('id')->on('staff')->restrictOnDelete();
            $table->foreign('approved_by')->references('id')->on('staff')->nullOnDelete();
            $table->index(['staff_id', 'change_type']);
            $table->index('effective_date');
        });

        Schema::table('staff', function (Blueprint $table) {
            $table->foreign('campus_id')->references('id')->on('campuses')->nullOnDelete();
            $table->foreign('line_manager_id')->references('id')->on('staff')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('staff', function (Blueprint $table) {
            if (Schema::hasColumn('staff', 'line_manager_id')) {
                $table->dropForeign(['line_manager_id']);
                $table->dropColumn('line_manager_id');
            }
            if (Schema::hasColumn('staff', 'campus_id')) {
                $table->dropForeign(['campus_id']);
                $table->dropColumn('campus_id');
            }
            if (Schema::hasColumn('staff', 'postal_code')) {
                $table->dropColumn('postal_code');
            }
            if (Schema::hasColumn('staff', 'home_county')) {
                $table->dropColumn('home_county');
            }
            if (Schema::hasColumn('staff', 'onboarding_completed_at')) {
                $table->dropColumn('onboarding_completed_at');
            }
            if (Schema::hasColumn('staff', 'is_profile_locked')) {
                $table->dropColumn('is_profile_locked');
            }
            if (Schema::hasColumn('staff', 'allowances_json')) {
                $table->dropColumn('allowances_json');
            }
            if (Schema::hasColumn('staff', 'project_code')) {
                $table->dropColumn('project_code');
            }
            if (Schema::hasColumn('staff', 'confirmation_date')) {
                $table->dropColumn('confirmation_date');
            }
            if (Schema::hasColumn('staff', 'incremental_date')) {
                $table->dropColumn('incremental_date');
            }
            if (Schema::hasColumn('staff', 'salary_scale')) {
                $table->dropColumn('salary_scale');
            }
            if (Schema::hasColumn('staff', 'line_manager_id')) {
                $table->dropForeign(['line_manager_id']);
                $table->dropColumn('line_manager_id');
            }
            if (Schema::hasColumn('staff', 'helb_number')) {
                $table->dropColumn('helb_number');
            }
            if (Schema::hasColumn('staff', 'marital_status')) {
                $table->dropColumn('marital_status');
            }
        });

        Schema::dropIfExists('staff_status_history');
        Schema::dropIfExists('staff_onboarding');
        Schema::dropIfExists('staff_documents');
        Schema::dropIfExists('staff_allowances');
        Schema::dropIfExists('staff_next_of_kin');
    }
};
