<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fee_structures', function (Blueprint $table) {
            $table->index('program_id', 'fee_structures_program_id_index');
        });

        Schema::table('fee_structures', function (Blueprint $table) {
            $table->dropUnique('fee_struct_unique');
        });

        Schema::table('fee_structures', function (Blueprint $table) {
            $table->decimal('application_fee', 12, 2)->default(1000)->after('semester_number');
            $table->decimal('caution_fee', 12, 2)->default(0)->after('tuition_fee');
            $table->decimal('computer_lab_fee', 12, 2)->default(0)->after('caution_fee');
            $table->decimal('transport_fee', 12, 2)->default(0)->after('computer_lab_fee');
            $table->tinyInteger('transport_optional')->default(1)->after('transport_fee');
            $table->decimal('accommodation_fee', 12, 2)->default(0)->after('transport_optional');
            $table->tinyInteger('accommodation_optional')->default(1)->after('accommodation_fee');
            $table->decimal('partnership_fee', 12, 2)->default(0)->after('accommodation_optional');
            $table->decimal('id_card_fee', 12, 2)->default(0)->after('partnership_fee');
            $table->decimal('student_union_fee', 12, 2)->default(0)->after('id_card_fee');
            $table->decimal('emergency_fund_fee', 12, 2)->default(0)->after('student_union_fee');
            $table->decimal('examination_external_fee', 12, 2)->default(0)->after('library_fee');
            $table->decimal('attachment_fee', 12, 2)->default(0)->after('examination_external_fee');
            $table->decimal('qa_annual_fee', 12, 2)->default(1000)->after('attachment_fee');
            $table->decimal('indexing_nck_fee', 12, 2)->nullable()->after('qa_annual_fee');
            $table->tinyInteger('requires_indexing_nck')->default(0)->after('indexing_nck_fee');
        });

        if (Schema::hasColumn('fee_structures', 'examination_fee')) {
            foreach (DB::table('fee_structures')->orderBy('id')->get() as $row) {
                DB::table('fee_structures')->where('id', $row->id)->update([
                    'application_fee' => $row->registration_fee ?? 1000,
                    'examination_external_fee' => $row->examination_fee ?? 0,
                    'accommodation_fee' => $row->hostel_fee ?? 0,
                    'attachment_fee' => $row->nursing_clinical_fee ?? 0,
                    'student_union_fee' => $row->activity_fee ?? 0,
                    'graduation_fee' => $row->graduation_fee ?? 4000,
                ]);
            }
        }

        Schema::table('fee_structures', function (Blueprint $table) {
            $table->dropColumn([
                'examination_fee',
                'activity_fee',
                'hostel_fee',
                'medical_insurance_fee',
                'nursing_clinical_fee',
                'registration_fee',
                'other_fees',
            ]);
        });

        Schema::table('fee_structures', function (Blueprint $table) {
            $table->dropColumn('semester_number');
            $table->unique(['program_id', 'academic_year_id'], 'fee_struct_program_year_unique');
        });
    }

    public function down(): void
    {
        Schema::table('fee_structures', function (Blueprint $table) {
            $table->dropUnique('fee_struct_program_year_unique');
        });

        Schema::table('fee_structures', function (Blueprint $table) {
            $table->decimal('examination_fee', 12, 2)->default(0);
            $table->decimal('activity_fee', 12, 2)->default(0);
            $table->decimal('hostel_fee', 12, 2)->default(0);
            $table->decimal('medical_insurance_fee', 12, 2)->default(0);
            $table->decimal('nursing_clinical_fee', 12, 2)->default(0);
            $table->decimal('registration_fee', 12, 2)->default(0);
            $table->json('other_fees')->nullable();
        });

        Schema::table('fee_structures', function (Blueprint $table) {
            $table->dropColumn([
                'application_fee',
                'caution_fee',
                'computer_lab_fee',
                'transport_fee',
                'transport_optional',
                'accommodation_fee',
                'accommodation_optional',
                'partnership_fee',
                'id_card_fee',
                'student_union_fee',
                'emergency_fund_fee',
                'examination_external_fee',
                'attachment_fee',
                'qa_annual_fee',
                'indexing_nck_fee',
                'requires_indexing_nck',
            ]);
        });

        Schema::table('fee_structures', function (Blueprint $table) {
            $table->dropUnique('fee_struct_program_year_unique');
            $table->unsignedInteger('semester_number')->default(1);
            $table->unique(['program_id', 'academic_year_id', 'semester_number'], 'fee_struct_unique');
            $table->dropIndex('fee_structures_program_id_index');
        });
    }
};
