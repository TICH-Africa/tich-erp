<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        app(\App\Services\Leave\LeaveCatalogService::class)->ensureSynced();

        if (Schema::hasTable('leave_requests')) {
            Schema::table('leave_requests', function (Blueprint $table) {
                if (! Schema::hasColumn('leave_requests', 'family_relation')) {
                    $table->string('family_relation', 30)->nullable()->after('reason');
                }
                if (! Schema::hasColumn('leave_requests', 'supporting_document_path')) {
                    $table->string('supporting_document_path', 500)->nullable()->after('medical_certificate_path');
                }
                if (! Schema::hasColumn('leave_requests', 'supporting_document_name')) {
                    $table->string('supporting_document_name', 255)->nullable()->after('supporting_document_path');
                }
                if (! Schema::hasColumn('leave_requests', 'sick_full_pay_days')) {
                    $table->unsignedSmallInteger('sick_full_pay_days')->nullable()->after('days_requested');
                }
                if (! Schema::hasColumn('leave_requests', 'sick_half_pay_days')) {
                    $table->unsignedSmallInteger('sick_half_pay_days')->nullable()->after('sick_full_pay_days');
                }
            });
        }

        if (! Schema::hasTable('leave_request_coverages')) {
            Schema::create('leave_request_coverages', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('leave_request_id');
                $table->unsignedBigInteger('department_id');
                $table->unsignedBigInteger('cover_staff_id');
                $table->string('status', 30)->default('accepted'); // auto-accepted
                $table->timestamp('notified_at')->nullable();
                $table->timestamp('access_granted_at')->nullable();
                $table->timestamp('access_revoked_at')->nullable();
                $table->timestamps();

                $table->unique(['leave_request_id', 'department_id'], 'leave_cov_req_dept_unique');
                $table->foreign('leave_request_id')->references('id')->on('leave_requests')->cascadeOnDelete();
                $table->foreign('department_id')->references('id')->on('departments')->cascadeOnDelete();
                $table->foreign('cover_staff_id')->references('id')->on('staff')->restrictOnDelete();
            });
        }

        if (! Schema::hasTable('leave_coverage_access_grants')) {
            Schema::create('leave_coverage_access_grants', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('leave_request_coverage_id');
                $table->unsignedBigInteger('cover_user_id');
                $table->unsignedBigInteger('role_id');
                $table->unsignedBigInteger('department_id');
                $table->unsignedBigInteger('campus_id')->nullable();
                $table->boolean('was_preexisting')->default(false);
                $table->timestamp('granted_at')->nullable();
                $table->timestamp('revoked_at')->nullable();
                $table->timestamps();

                $table->foreign('leave_request_coverage_id', 'leave_cov_grant_cov_fk')
                    ->references('id')->on('leave_request_coverages')->cascadeOnDelete();
                $table->foreign('cover_user_id')->references('id')->on('users')->cascadeOnDelete();
                $table->foreign('role_id')->references('id')->on('roles')->cascadeOnDelete();
                $table->foreign('department_id')->references('id')->on('departments')->cascadeOnDelete();
            });
        }

        if (! Schema::hasTable('leave_sick_pay_adjustments')) {
            Schema::create('leave_sick_pay_adjustments', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('leave_request_id');
                $table->unsignedBigInteger('staff_id');
                $table->unsignedSmallInteger('year');
                $table->unsignedTinyInteger('month');
                $table->unsignedSmallInteger('half_pay_days');
                $table->decimal('daily_rate', 12, 2)->nullable();
                $table->decimal('deduction_amount', 12, 2)->nullable();
                $table->string('status', 30)->default('pending'); // pending|applied|reversed
                $table->timestamp('applied_at')->nullable();
                $table->timestamps();

                $table->unique(['leave_request_id', 'year', 'month'], 'leave_sick_pay_unique');
                $table->foreign('leave_request_id')->references('id')->on('leave_requests')->cascadeOnDelete();
                $table->foreign('staff_id')->references('id')->on('staff')->cascadeOnDelete();
            });
        }

        if (Schema::hasTable('leave_carry_forward_requests')) {
            Schema::table('leave_carry_forward_requests', function (Blueprint $table) {
                if (! Schema::hasColumn('leave_carry_forward_requests', 'line_manager_status')) {
                    $table->string('line_manager_status', 30)->default('pending')->after('status');
                }
                if (! Schema::hasColumn('leave_carry_forward_requests', 'line_manager_staff_id')) {
                    $table->unsignedBigInteger('line_manager_staff_id')->nullable()->after('line_manager_status');
                }
                if (! Schema::hasColumn('leave_carry_forward_requests', 'line_manager_acted_at')) {
                    $table->timestamp('line_manager_acted_at')->nullable()->after('line_manager_staff_id');
                }
                if (! Schema::hasColumn('leave_carry_forward_requests', 'line_manager_notes')) {
                    $table->text('line_manager_notes')->nullable()->after('line_manager_acted_at');
                }
                if (! Schema::hasColumn('leave_carry_forward_requests', 'hr_status')) {
                    $table->string('hr_status', 30)->default('pending')->after('line_manager_notes');
                }
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_sick_pay_adjustments');
        Schema::dropIfExists('leave_coverage_access_grants');
        Schema::dropIfExists('leave_request_coverages');

        if (Schema::hasTable('leave_requests')) {
            Schema::table('leave_requests', function (Blueprint $table) {
                foreach (['family_relation', 'supporting_document_path', 'supporting_document_name', 'sick_full_pay_days', 'sick_half_pay_days'] as $col) {
                    if (Schema::hasColumn('leave_requests', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }

        if (Schema::hasTable('leave_carry_forward_requests')) {
            Schema::table('leave_carry_forward_requests', function (Blueprint $table) {
                foreach (['line_manager_status', 'line_manager_staff_id', 'line_manager_acted_at', 'line_manager_notes', 'hr_status'] as $col) {
                    if (Schema::hasColumn('leave_carry_forward_requests', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }

        // Soft-disable compensatory / restore COMP days is handled by catalog sync; do not delete types.
        DB::table('leave_types')->where('leave_code', 'COMPOFF')->update(['is_active' => 0]);
    }
};
