<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('academic_programs', function (Blueprint $table) {
            if (! Schema::hasColumn('academic_programs', 'is_tiered')) {
                $table->boolean('is_tiered')->default(false)->after('curriculum_format');
            }
            if (! Schema::hasColumn('academic_programs', 'tier_semester_months')) {
                $table->unsignedTinyInteger('tier_semester_months')->nullable()->after('is_tiered');
            }
        });

        if (! Schema::hasTable('program_tiers')) {
            Schema::create('program_tiers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('program_id')->constrained('academic_programs')->cascadeOnDelete();
                $table->string('tier_key', 32);
                $table->unsignedTinyInteger('tier_order');
                $table->unsignedSmallInteger('cumulative_months');
                $table->text('learning_outcomes')->nullable();
                $table->text('marketing_summary')->nullable();
                $table->timestamps();

                $table->unique(['program_id', 'tier_key']);
                $table->unique(['program_id', 'tier_order']);
            });
        }

        Schema::table('program_units', function (Blueprint $table) {
            if (! Schema::hasColumn('program_units', 'program_tier')) {
                $table->string('program_tier', 32)->nullable()->after('semester');
            }
        });

        Schema::table('applicants', function (Blueprint $table) {
            if (! Schema::hasColumn('applicants', 'program_tier')) {
                $table->string('program_tier', 32)->nullable()->after('program_id');
            }
        });

        Schema::table('students', function (Blueprint $table) {
            if (! Schema::hasColumn('students', 'program_tier')) {
                $table->string('program_tier', 32)->nullable()->after('program_id');
            }
            if (! Schema::hasColumn('students', 'program_tier_history')) {
                $table->json('program_tier_history')->nullable()->after('program_tier');
            }
            if (! Schema::hasColumn('students', 'paid_program_tiers')) {
                $table->json('paid_program_tiers')->nullable()->after('program_tier_history');
            }
        });

        Schema::table('fee_structures', function (Blueprint $table) {
            if (! Schema::hasColumn('fee_structures', 'program_tier')) {
                $table->string('program_tier', 32)->nullable()->after('program_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('fee_structures', function (Blueprint $table) {
            if (Schema::hasColumn('fee_structures', 'program_tier')) {
                $table->dropColumn('program_tier');
            }
        });

        Schema::table('students', function (Blueprint $table) {
            foreach (['paid_program_tiers', 'program_tier_history', 'program_tier'] as $col) {
                if (Schema::hasColumn('students', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::table('applicants', function (Blueprint $table) {
            if (Schema::hasColumn('applicants', 'program_tier')) {
                $table->dropColumn('program_tier');
            }
        });

        Schema::table('program_units', function (Blueprint $table) {
            if (Schema::hasColumn('program_units', 'program_tier')) {
                $table->dropColumn('program_tier');
            }
        });

        Schema::dropIfExists('program_tiers');

        Schema::table('academic_programs', function (Blueprint $table) {
            foreach (['tier_semester_months', 'is_tiered'] as $col) {
                if (Schema::hasColumn('academic_programs', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
