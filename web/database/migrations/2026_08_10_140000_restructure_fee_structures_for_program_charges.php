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
            if (! Schema::hasIndex('fee_structures', 'fee_structures_program_id_index')) {
                $table->index('program_id', 'fee_structures_program_id_index');
            }
        });

        Schema::table('fee_structures', function (Blueprint $table) {
            if (Schema::hasIndex('fee_structures', 'fee_struct_unique')) {
                $table->dropUnique('fee_struct_unique');
            }
        });

        Schema::table('fee_structures', function (Blueprint $table) {
            if (! Schema::hasColumn('fee_structures', 'application_fee')) {
                $table->decimal('application_fee', 12, 2)->default(1000)->after('semester_number');
            }
            if (! Schema::hasColumn('fee_structures', 'caution_fee')) {
                $table->decimal('caution_fee', 12, 2)->default(0)->after('tuition_fee');
            }
            if (! Schema::hasColumn('fee_structures', 'computer_lab_fee')) {
                $table->decimal('computer_lab_fee', 12, 2)->default(0)->after('caution_fee');
            }
            if (! Schema::hasColumn('fee_structures', 'transport_fee')) {
                $table->decimal('transport_fee', 12, 2)->default(0)->after('computer_lab_fee');
            }
            if (! Schema::hasColumn('fee_structures', 'transport_optional')) {
                $table->tinyInteger('transport_optional')->default(1)->after('transport_fee');
            }
            if (! Schema::hasColumn('fee_structures', 'accommodation_fee')) {
                $table->decimal('accommodation_fee', 12, 2)->default(0)->after('transport_optional');
            }
            if (! Schema::hasColumn('fee_structures', 'accommodation_optional')) {
                $table->tinyInteger('accommodation_optional')->default(1)->after('accommodation_fee');
            }
            if (! Schema::hasColumn('fee_structures', 'partnership_fee')) {
                $table->decimal('partnership_fee', 12, 2)->default(0)->after('accommodation_optional');
            }
            if (! Schema::hasColumn('fee_structures', 'id_card_fee')) {
                $table->decimal('id_card_fee', 12, 2)->default(0)->after('partnership_fee');
            }
            if (! Schema::hasColumn('fee_structures', 'student_union_fee')) {
                $table->decimal('student_union_fee', 12, 2)->default(0)->after('id_card_fee');
            }
            if (! Schema::hasColumn('fee_structures', 'emergency_fund_fee')) {
                $table->decimal('emergency_fund_fee', 12, 2)->default(0)->after('student_union_fee');
            }
            if (! Schema::hasColumn('fee_structures', 'examination_external_fee')) {
                $table->decimal('examination_external_fee', 12, 2)->default(0)->after('library_fee');
            }
            if (! Schema::hasColumn('fee_structures', 'attachment_fee')) {
                $table->decimal('attachment_fee', 12, 2)->default(0)->after('examination_external_fee');
            }
            if (! Schema::hasColumn('fee_structures', 'qa_annual_fee')) {
                $table->decimal('qa_annual_fee', 12, 2)->default(1000)->after('attachment_fee');
            }
            if (! Schema::hasColumn('fee_structures', 'indexing_nck_fee')) {
                $table->decimal('indexing_nck_fee', 12, 2)->nullable()->after('qa_annual_fee');
            }
            if (! Schema::hasColumn('fee_structures', 'requires_indexing_nck')) {
                $table->tinyInteger('requires_indexing_nck')->default(0)->after('indexing_nck_fee');
            }
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
            $toDrop = array_filter([
                Schema::hasColumn('fee_structures', 'examination_fee') ? 'examination_fee' : null,
                Schema::hasColumn('fee_structures', 'activity_fee') ? 'activity_fee' : null,
                Schema::hasColumn('fee_structures', 'hostel_fee') ? 'hostel_fee' : null,
                Schema::hasColumn('fee_structures', 'medical_insurance_fee') ? 'medical_insurance_fee' : null,
                Schema::hasColumn('fee_structures', 'nursing_clinical_fee') ? 'nursing_clinical_fee' : null,
                Schema::hasColumn('fee_structures', 'registration_fee') ? 'registration_fee' : null,
                Schema::hasColumn('fee_structures', 'other_fees') ? 'other_fees' : null,
            ]);

            if (! empty($toDrop)) {
                $table->dropColumn($toDrop);
            }
        });

        Schema::table('fee_structures', function (Blueprint $table) {
            if (Schema::hasColumn('fee_structures', 'semester_number')) {
                $table->dropColumn('semester_number');
            }
            if (! Schema::hasIndex('fee_structures', 'fee_struct_program_year_unique')) {
                $table->unique(['program_id', 'academic_year_id'], 'fee_struct_program_year_unique');
            }
        });
    }

    public function down(): void
    {
        Schema::table('fee_structures', function (Blueprint $table) {
            if (Schema::hasIndex('fee_structures', 'fee_struct_program_year_unique')) {
                $table->dropUnique('fee_struct_program_year_unique');
            }
        });

        Schema::table('fee_structures', function (Blueprint $table) {
            $toDrop = array_filter([
                Schema::hasColumn('fee_structures', 'application_fee') ? 'application_fee' : null,
                Schema::hasColumn('fee_structures', 'caution_fee') ? 'caution_fee' : null,
                Schema::hasColumn('fee_structures', 'computer_lab_fee') ? 'computer_lab_fee' : null,
                Schema::hasColumn('fee_structures', 'transport_fee') ? 'transport_fee' : null,
                Schema::hasColumn('fee_structures', 'transport_optional') ? 'transport_optional' : null,
                Schema::hasColumn('fee_structures', 'accommodation_fee') ? 'accommodation_fee' : null,
                Schema::hasColumn('fee_structures', 'accommodation_optional') ? 'accommodation_optional' : null,
                Schema::hasColumn('fee_structures', 'partnership_fee') ? 'partnership_fee' : null,
                Schema::hasColumn('fee_structures', 'id_card_fee') ? 'id_card_fee' : null,
                Schema::hasColumn('fee_structures', 'student_union_fee') ? 'student_union_fee' : null,
                Schema::hasColumn('fee_structures', 'emergency_fund_fee') ? 'emergency_fund_fee' : null,
                Schema::hasColumn('fee_structures', 'examination_external_fee') ? 'examination_external_fee' : null,
                Schema::hasColumn('fee_structures', 'attachment_fee') ? 'attachment_fee' : null,
                Schema::hasColumn('fee_structures', 'qa_annual_fee') ? 'qa_annual_fee' : null,
                Schema::hasColumn('fee_structures', 'indexing_nck_fee') ? 'indexing_nck_fee' : null,
                Schema::hasColumn('fee_structures', 'requires_indexing_nck') ? 'requires_indexing_nck' : null,
            ]);

            if (! empty($toDrop)) {
                $table->dropColumn($toDrop);
            }
        });

        Schema::table('fee_structures', function (Blueprint $table) {
            $toAdd = [];
            if (! Schema::hasColumn('fee_structures', 'examination_fee')) {
                $toAdd[] = 'examination_fee';
            }
            if (! Schema::hasColumn('fee_structures', 'activity_fee')) {
                $toAdd[] = 'activity_fee';
            }
            if (! Schema::hasColumn('fee_structures', 'hostel_fee')) {
                $toAdd[] = 'hostel_fee';
            }
            if (! Schema::hasColumn('fee_structures', 'medical_insurance_fee')) {
                $toAdd[] = 'medical_insurance_fee';
            }
            if (! Schema::hasColumn('fee_structures', 'nursing_clinical_fee')) {
                $toAdd[] = 'nursing_clinical_fee';
            }
            if (! Schema::hasColumn('fee_structures', 'registration_fee')) {
                $toAdd[] = 'registration_fee';
            }
            if (! Schema::hasColumn('fee_structures', 'other_fees')) {
                $toAdd[] = 'other_fees';
            }

            foreach ($toAdd as $column) {
                $table->decimal($column, 12, 2)->default(0);
            }

            if (! Schema::hasColumn('fee_structures', 'other_fees')) {
                $table->json('other_fees')->nullable();
            }
        });

        Schema::table('fee_structures', function (Blueprint $table) {
            if (! Schema::hasColumn('fee_structures', 'semester_number')) {
                $table->unsignedInteger('semester_number')->default(1);
            }
            if (! Schema::hasIndex('fee_structures', 'fee_struct_unique')) {
                $table->unique(['program_id', 'academic_year_id', 'semester_number'], 'fee_struct_unique');
            }
            if (Schema::hasIndex('fee_structures', 'fee_structures_program_id_index')) {
                $table->dropIndex('fee_structures_program_id_index');
            }
        });
    }
};
