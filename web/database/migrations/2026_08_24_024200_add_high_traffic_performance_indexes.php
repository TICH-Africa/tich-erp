<?php

use App\Support\Database\EnsuresIndexes;
use Illuminate\Database\Migrations\Migration;

/**
 * High-traffic BTREE indexes so equality/filter lookups avoid full table scans.
 *
 * Example: finding applicant/student/invoice row ~4395 among thousands should
 * use an index seek, not walk rows 1..4394.
 *
 * Safe to re-run: skips missing tables/columns and existing equivalent indexes.
 * Future migrations: use EnsuresIndexes::ensureIndex() when adding filter columns.
 */
return new class extends Migration
{
    use EnsuresIndexes;

    public function up(): void
    {
        $indexes = [
            // Admissions
            ['applicants', 'applicants_status_index', ['status']],
            ['applicants', 'applicants_status_program_id_index', ['status', 'program_id']],
            ['applicants', 'applicants_email_index', ['email']],
            ['applicants', 'applicants_created_at_index', ['created_at']],
            ['applicants', 'applicants_application_number_index', ['application_number']],
            ['applicants', 'applicants_academic_review_status_index', ['academic_review_status']],

            // SIS / academics
            ['students', 'students_enrollment_status_index', ['enrollment_status']],
            ['students', 'students_program_enrollment_status_index', ['program_id', 'enrollment_status']],
            ['students', 'students_application_id_index', ['application_id']],
            ['academic_programs', 'academic_programs_status_index', ['status']],
            ['academic_programs', 'academic_programs_department_status_index', ['department_id', 'status']],
            ['units', 'units_status_index', ['status']],
            ['units', 'units_department_status_index', ['department_id', 'status']],
            ['lesson_plans', 'lesson_plans_status_index', ['status']],
            ['lesson_plans', 'lesson_plans_allocation_status_date_index', ['unit_allocation_id', 'status', 'planned_date']],

            // Finance
            ['payments', 'payments_status_index', ['status']],
            ['payments', 'payments_payment_date_index', ['payment_date']],
            ['payments', 'payments_invoice_id_status_index', ['invoice_id', 'status']],
            ['invoices', 'invoices_student_id_status_index', ['student_id', 'status']],
            ['invoices', 'invoices_status_due_date_index', ['status', 'due_date']],
            ['financial_adjustments', 'financial_adjustments_status_index', ['status']],
            ['refunds', 'refunds_status_index', ['status']],
            ['installment_plans', 'installment_plans_status_index', ['status']],
            ['payroll_runs', 'payroll_runs_status_index', ['status']],
            ['payroll_runs', 'payroll_runs_period_index', ['pay_period_year', 'pay_period_month']],

            // HR
            ['staff', 'staff_department_employment_status_index', ['department_id', 'employment_status']],
            ['leave_requests', 'leave_requests_staff_overall_status_index', ['staff_id', 'overall_status']],
            ['leave_requests', 'leave_requests_overall_status_created_at_index', ['overall_status', 'created_at']],
            ['leave_carry_forward_requests', 'leave_carry_forward_requests_status_index', ['status']],
            ['job_vacancies', 'job_vacancies_status_index', ['status']],
            ['recruitment_applications', 'recruitment_applications_status_index', ['status']],

            // Platform / audit / admin
            ['users', 'users_is_active_index', ['is_active']],
            ['users', 'users_staff_id_index', ['staff_id']],
            ['users', 'users_student_id_index', ['student_id']],
            ['audit_logs', 'audit_logs_module_index', ['module']],
            ['audit_logs', 'audit_logs_status_index', ['status']],
            ['communication_logs', 'communication_logs_created_at_index', ['created_at']],
            ['departments', 'departments_dept_code_index', ['dept_code']],
            ['departments', 'departments_parent_active_index', ['parent_dept_id', 'is_active']],
            ['admin_budget_requests', 'admin_budget_requests_submitted_by_index', ['submitted_by']],
            ['admin_budget_requests', 'admin_budget_requests_submitted_at_index', ['submitted_at']],
            ['admin_statutory_certifications', 'admin_statutory_certifications_status_expires_index', ['status', 'expires_on']],
            ['erp_registration_invitations', 'erp_registration_invitations_email_index', ['email']],
            ['erp_registration_invitations', 'erp_registration_invitations_status_index', ['status']],
        ];

        foreach ($indexes as [$table, $name, $columns]) {
            $this->ensureIndex($table, $name, $columns);
        }
    }

    public function down(): void
    {
        $names = [
            ['applicants', 'applicants_status_index'],
            ['applicants', 'applicants_status_program_id_index'],
            ['applicants', 'applicants_email_index'],
            ['applicants', 'applicants_created_at_index'],
            ['applicants', 'applicants_application_number_index'],
            ['applicants', 'applicants_academic_review_status_index'],
            ['students', 'students_enrollment_status_index'],
            ['students', 'students_program_enrollment_status_index'],
            ['students', 'students_application_id_index'],
            ['academic_programs', 'academic_programs_status_index'],
            ['academic_programs', 'academic_programs_department_status_index'],
            ['units', 'units_status_index'],
            ['units', 'units_department_status_index'],
            ['lesson_plans', 'lesson_plans_status_index'],
            ['lesson_plans', 'lesson_plans_allocation_status_date_index'],
            ['payments', 'payments_status_index'],
            ['payments', 'payments_payment_date_index'],
            ['payments', 'payments_invoice_id_status_index'],
            ['invoices', 'invoices_student_id_status_index'],
            ['invoices', 'invoices_status_due_date_index'],
            ['financial_adjustments', 'financial_adjustments_status_index'],
            ['refunds', 'refunds_status_index'],
            ['installment_plans', 'installment_plans_status_index'],
            ['payroll_runs', 'payroll_runs_status_index'],
            ['payroll_runs', 'payroll_runs_period_index'],
            ['staff', 'staff_department_employment_status_index'],
            ['leave_requests', 'leave_requests_staff_overall_status_index'],
            ['leave_requests', 'leave_requests_overall_status_created_at_index'],
            ['leave_carry_forward_requests', 'leave_carry_forward_requests_status_index'],
            ['job_vacancies', 'job_vacancies_status_index'],
            ['recruitment_applications', 'recruitment_applications_status_index'],
            ['users', 'users_is_active_index'],
            ['users', 'users_staff_id_index'],
            ['users', 'users_student_id_index'],
            ['audit_logs', 'audit_logs_module_index'],
            ['audit_logs', 'audit_logs_status_index'],
            ['communication_logs', 'communication_logs_created_at_index'],
            ['departments', 'departments_dept_code_index'],
            ['departments', 'departments_parent_active_index'],
            ['admin_budget_requests', 'admin_budget_requests_submitted_by_index'],
            ['admin_budget_requests', 'admin_budget_requests_submitted_at_index'],
            ['admin_statutory_certifications', 'admin_statutory_certifications_status_expires_index'],
            ['erp_registration_invitations', 'erp_registration_invitations_email_index'],
            ['erp_registration_invitations', 'erp_registration_invitations_status_index'],
        ];

        foreach ($names as [$table, $name]) {
            $this->dropIndexIfExists($table, $name);
        }
    }
};
