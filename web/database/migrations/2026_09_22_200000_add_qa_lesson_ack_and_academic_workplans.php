<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('lesson_plans')) {
            Schema::table('lesson_plans', function (Blueprint $table) {
                if (! Schema::hasColumn('lesson_plans', 'qa_acknowledged_by')) {
                    $table->unsignedBigInteger('qa_acknowledged_by')->nullable()->after('registrar_visible');
                }
                if (! Schema::hasColumn('lesson_plans', 'qa_acknowledged_at')) {
                    $table->dateTime('qa_acknowledged_at')->nullable()->after('qa_acknowledged_by');
                }
                if (! Schema::hasColumn('lesson_plans', 'qa_comments')) {
                    $table->text('qa_comments')->nullable()->after('qa_acknowledged_at');
                }
            });

            Schema::table('lesson_plans', function (Blueprint $table) {
                if (Schema::hasColumn('lesson_plans', 'qa_acknowledged_by')) {
                    try {
                        $table->foreign('qa_acknowledged_by')->references('id')->on('staff')->nullOnDelete();
                    } catch (\Throwable) {
                        // FK may already exist
                    }
                }
            });
        }

        if (! Schema::hasTable('academic_workplans')) {
            Schema::create('academic_workplans', function (Blueprint $table) {
                $table->id();
                $table->string('workplan_number', 40)->unique();
                $table->unsignedBigInteger('department_id');
                $table->unsignedBigInteger('semester_id');
                $table->string('title', 300);
                $table->text('objectives')->nullable();
                $table->text('resources')->nullable();
                $table->text('kpis')->nullable();
                $table->string('status', 40)->default('draft'); // draft|pending|approved|rejected|changes_requested
                $table->unsignedBigInteger('prepared_by_staff_id');
                $table->timestamp('submitted_at')->nullable();

                $table->string('registrar_status', 40)->default('pending'); // pending|approved|rejected|changes_requested
                $table->unsignedBigInteger('registrar_staff_id')->nullable();
                $table->timestamp('registrar_acted_at')->nullable();
                $table->text('registrar_comments')->nullable();

                $table->string('qa_status', 40)->default('pending');
                $table->unsignedBigInteger('qa_staff_id')->nullable();
                $table->timestamp('qa_acted_at')->nullable();
                $table->text('qa_comments')->nullable();

                $table->timestamps();

                $table->index(['department_id', 'semester_id', 'status'], 'awp_dept_sem_status_idx');
                $table->foreign('department_id')->references('id')->on('departments')->cascadeOnDelete();
                $table->foreign('semester_id')->references('id')->on('semesters')->restrictOnDelete();
                $table->foreign('prepared_by_staff_id')->references('id')->on('staff')->restrictOnDelete();
                $table->foreign('registrar_staff_id')->references('id')->on('staff')->nullOnDelete();
                $table->foreign('qa_staff_id')->references('id')->on('staff')->nullOnDelete();
            });
        }

        if (! Schema::hasTable('academic_workplan_activities')) {
            Schema::create('academic_workplan_activities', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('workplan_id');
                $table->string('activity', 500);
                $table->date('timeline_start')->nullable();
                $table->date('timeline_end')->nullable();
                $table->string('kpi', 500)->nullable();
                $table->string('resources', 500)->nullable();
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->timestamps();

                $table->foreign('workplan_id')->references('id')->on('academic_workplans')->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('academic_workplan_activities');
        Schema::dropIfExists('academic_workplans');

        if (Schema::hasTable('lesson_plans')) {
            Schema::table('lesson_plans', function (Blueprint $table) {
                if (Schema::hasColumn('lesson_plans', 'qa_acknowledged_by')) {
                    try {
                        $table->dropForeign(['qa_acknowledged_by']);
                    } catch (\Throwable) {
                    }
                }
                foreach (['qa_comments', 'qa_acknowledged_at', 'qa_acknowledged_by'] as $col) {
                    if (Schema::hasColumn('lesson_plans', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};
