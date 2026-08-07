<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leave_types', function (Blueprint $table) {
            if (! Schema::hasColumn('leave_types', 'accrual_type')) {
                $table->string('accrual_type', 50)->default('none')->after('days_allowed_per_year');
            }
            if (! Schema::hasColumn('leave_types', 'accrual_rate')) {
                $table->decimal('accrual_rate', 6, 2)->nullable()->after('accrual_type');
            }
            if (! Schema::hasColumn('leave_types', 'calculation_type')) {
                $table->string('calculation_type', 50)->default('calendar_days')->after('accrual_rate');
            }
            if (! Schema::hasColumn('leave_types', 'is_paid')) {
                $table->tinyInteger('is_paid')->default(1)->after('calculation_type');
            }
            if (! Schema::hasColumn('leave_types', 'requires_certificate')) {
                $table->tinyInteger('requires_certificate')->default(0)->after('requires_medical_certificate');
            }
            if (! Schema::hasColumn('leave_types', 'requires_hod_approval')) {
                $table->tinyInteger('requires_hod_approval')->default(1)->after('requires_certificate');
            }
            if (! Schema::hasColumn('leave_types', 'requires_hr_approval')) {
                $table->tinyInteger('requires_hr_approval')->default(1)->after('requires_hod_approval');
            }
            if (! Schema::hasColumn('leave_types', 'max_consecutive_days')) {
                $table->integer('max_consecutive_days')->nullable()->after('carry_forward_days');
            }
            if (! Schema::hasColumn('leave_types', 'notice_period_days')) {
                $table->integer('notice_period_days')->default(0)->after('max_consecutive_days');
            }
            if (! Schema::hasColumn('leave_types', 'description')) {
                $table->text('description')->nullable()->after('notice_period_days');
            }
        });
    }

    public function down(): void
    {
        Schema::table('leave_types', function (Blueprint $table) {
            $columns = [
                'accrual_type',
                'accrual_rate',
                'calculation_type',
                'is_paid',
                'requires_certificate',
                'requires_hod_approval',
                'requires_hr_approval',
                'max_consecutive_days',
                'notice_period_days',
                'description',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('leave_types', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
