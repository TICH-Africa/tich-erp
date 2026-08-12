<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('installment_plans')) {
            Schema::table('installment_plans', function (Blueprint $table) {
                if (! Schema::hasColumn('installment_plans', 'academic_year_id')) {
                    $table->unsignedBigInteger('academic_year_id')->nullable()->after('invoice_id');
                }
                if (! Schema::hasColumn('installment_plans', 'semester_id')) {
                    $table->unsignedBigInteger('semester_id')->nullable()->after('academic_year_id');
                }
            });

            if (Schema::hasTable('invoices') && Schema::hasTable('semesters')) {
                $plans = DB::table('installment_plans as ip')
                    ->leftJoin('invoices as i', 'ip.invoice_id', '=', 'i.id')
                    ->leftJoin('semesters as s', 'i.semester_id', '=', 's.id')
                    ->select('ip.id', 'i.semester_id', 's.academic_year_id')
                    ->whereNull('ip.semester_id')
                    ->get();

                foreach ($plans as $plan) {
                    DB::table('installment_plans')
                        ->where('id', $plan->id)
                        ->update([
                            'semester_id' => $plan->semester_id,
                            'academic_year_id' => $plan->academic_year_id,
                        ]);
                }
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('installment_plans')) {
            Schema::table('installment_plans', function (Blueprint $table) {
                $cols = array_filter([
                    Schema::hasColumn('installment_plans', 'academic_year_id') ? 'academic_year_id' : null,
                    Schema::hasColumn('installment_plans', 'semester_id') ? 'semester_id' : null,
                ]);
                if (! empty($cols)) {
                    $table->dropColumn($cols);
                }
            });
        }
    }
};
