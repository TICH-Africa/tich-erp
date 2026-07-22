<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            if (! Schema::hasColumn('departments', 'curriculum_profile')) {
                $table->string('curriculum_profile', 50)->default('standard')->after('dept_category');
            }
            if (! Schema::hasColumn('departments', 'approval_status')) {
                $table->string('approval_status', 50)->default('active')->after('is_active');
            }
        });

        Schema::table('academic_programs', function (Blueprint $table) {
            if (! Schema::hasColumn('academic_programs', 'curriculum_format')) {
                $table->string('curriculum_format', 50)->default('trimester')->after('regulatory_body');
            }
        });

        Schema::table('units', function (Blueprint $table) {
            if (! Schema::hasColumn('units', 'department_id')) {
                $table->unsignedBigInteger('department_id')->nullable()->after('unit_name');
                $table->foreign('department_id')->references('id')->on('departments')->nullOnDelete();
            }
            if (! Schema::hasColumn('units', 'total_learning_hours')) {
                $table->integer('total_learning_hours')->default(0)->after('contact_hours');
            }
            if (! Schema::hasColumn('units', 'display_priority')) {
                $table->integer('display_priority')->default(0)->after('total_learning_hours');
            }
            if (! Schema::hasColumn('units', 'description')) {
                $table->text('description')->nullable()->after('unit_name');
            }
            if (! Schema::hasColumn('units', 'submitted_at')) {
                $table->dateTime('submitted_at')->nullable()->after('status');
            }
            if (! Schema::hasColumn('units', 'submitted_by')) {
                $table->unsignedBigInteger('submitted_by')->nullable()->after('submitted_at');
            }
            if (! Schema::hasColumn('units', 'registrar_approved_at')) {
                $table->dateTime('registrar_approved_at')->nullable()->after('submitted_by');
            }
            if (! Schema::hasColumn('units', 'registrar_approved_by')) {
                $table->unsignedBigInteger('registrar_approved_by')->nullable()->after('registrar_approved_at');
            }
        });

        Schema::table('program_units', function (Blueprint $table) {
            if (! Schema::hasColumn('program_units', 'display_order')) {
                $table->integer('display_order')->default(0)->after('is_compulsory');
            }
            if (! Schema::hasColumn('program_units', 'priority')) {
                $table->integer('priority')->default(0)->after('display_order');
            }
            if (! Schema::hasColumn('program_units', 'contact_hours')) {
                $table->integer('contact_hours')->default(0)->after('priority');
            }
            if (! Schema::hasColumn('program_units', 'total_learning_hours')) {
                $table->integer('total_learning_hours')->default(0)->after('contact_hours');
            }
        });

        if (! Schema::hasTable('curriculum_versions')) {
            Schema::create('curriculum_versions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('program_id');
                $table->unsignedBigInteger('academic_year_id')->nullable();
                $table->string('version_label', 100);
                $table->integer('version_number')->default(1);
                $table->string('curriculum_format', 50);
                $table->string('status', 50)->default('draft');
                $table->text('notes')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->dateTime('submitted_at')->nullable();
                $table->unsignedBigInteger('submitted_by')->nullable();
                $table->dateTime('registrar_approved_at')->nullable();
                $table->unsignedBigInteger('registrar_approved_by')->nullable();
                $table->dateTime('ceo_approved_at')->nullable();
                $table->unsignedBigInteger('ceo_approved_by')->nullable();
                $table->dateTime('published_at')->nullable();
                $table->unsignedBigInteger('published_by')->nullable();
                $table->dateTime('created_at')->useCurrent();
                $table->dateTime('updated_at')->nullable()->useCurrentOnUpdate();
                $table->foreign('program_id')->references('id')->on('academic_programs')->restrictOnDelete();
                $table->foreign('academic_year_id')->references('id')->on('academic_years')->nullOnDelete();
                $table->index(['program_id', 'status']);
            });
        }

        if (! Schema::hasTable('curriculum_version_units')) {
            Schema::create('curriculum_version_units', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('curriculum_version_id');
                $table->unsignedBigInteger('unit_id');
                $table->integer('semester')->default(1);
                $table->unsignedBigInteger('block_id')->nullable();
                $table->tinyInteger('is_compulsory')->default(1);
                $table->integer('display_order')->default(0);
                $table->integer('priority')->default(0);
                $table->decimal('credit_hours', 5, 2)->default(0);
                $table->integer('contact_hours')->default(0);
                $table->integer('total_learning_hours')->default(0);
                $table->foreign('curriculum_version_id')->references('id')->on('curriculum_versions')->cascadeOnDelete();
                $table->foreign('unit_id')->references('id')->on('units')->restrictOnDelete();
                $table->foreign('block_id')->references('id')->on('nursing_blocks')->nullOnDelete();
                $table->unique(['curriculum_version_id', 'unit_id'], 'cv_unit_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('curriculum_version_units');
        Schema::dropIfExists('curriculum_versions');

        Schema::table('program_units', function (Blueprint $table) {
            foreach (['display_order', 'priority', 'contact_hours', 'total_learning_hours'] as $column) {
                if (Schema::hasColumn('program_units', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('units', function (Blueprint $table) {
            foreach ([
                'department_id', 'total_learning_hours', 'display_priority', 'description',
                'submitted_at', 'submitted_by', 'registrar_approved_at', 'registrar_approved_by',
            ] as $column) {
                if (Schema::hasColumn('units', $column)) {
                    if ($column === 'department_id') {
                        $table->dropForeign(['department_id']);
                    }
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('academic_programs', function (Blueprint $table) {
            if (Schema::hasColumn('academic_programs', 'curriculum_format')) {
                $table->dropColumn('curriculum_format');
            }
        });

        Schema::table('departments', function (Blueprint $table) {
            foreach (['curriculum_profile', 'approval_status'] as $column) {
                if (Schema::hasColumn('departments', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
