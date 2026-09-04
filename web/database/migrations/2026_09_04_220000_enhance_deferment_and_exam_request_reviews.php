<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('student_lifecycle_requests')) {
            Schema::table('student_lifecycle_requests', function (Blueprint $table) {
                if (! Schema::hasColumn('student_lifecycle_requests', 'deferment_months')) {
                    $table->unsignedSmallInteger('deferment_months')->nullable()->after('effective_date');
                }
                if (! Schema::hasColumn('student_lifecycle_requests', 'attachments')) {
                    $table->json('attachments')->nullable()->after('reason');
                }
                if (! Schema::hasColumn('student_lifecycle_requests', 'registrar_status')) {
                    $table->string('registrar_status', 30)->default('pending')->after('status');
                }
                if (! Schema::hasColumn('student_lifecycle_requests', 'dean_status')) {
                    $table->string('dean_status', 30)->default('pending')->after('registrar_status');
                }
                if (! Schema::hasColumn('student_lifecycle_requests', 'registrar_notes')) {
                    $table->text('registrar_notes')->nullable()->after('reviewer_notes');
                }
                if (! Schema::hasColumn('student_lifecycle_requests', 'dean_notes')) {
                    $table->text('dean_notes')->nullable()->after('registrar_notes');
                }
                if (! Schema::hasColumn('student_lifecycle_requests', 'registrar_reviewed_by_user_id')) {
                    $table->unsignedBigInteger('registrar_reviewed_by_user_id')->nullable()->after('dean_notes');
                }
                if (! Schema::hasColumn('student_lifecycle_requests', 'dean_reviewed_by_user_id')) {
                    $table->unsignedBigInteger('dean_reviewed_by_user_id')->nullable()->after('registrar_reviewed_by_user_id');
                }
                if (! Schema::hasColumn('student_lifecycle_requests', 'registrar_reviewed_at')) {
                    $table->timestamp('registrar_reviewed_at')->nullable()->after('dean_reviewed_by_user_id');
                }
                if (! Schema::hasColumn('student_lifecycle_requests', 'dean_reviewed_at')) {
                    $table->timestamp('dean_reviewed_at')->nullable()->after('registrar_reviewed_at');
                }
            });
        }

        if (Schema::hasTable('special_exam_requests') && ! Schema::hasColumn('special_exam_requests', 'status')) {
            // status already exists
        }

        // Ensure on_hold is usable via string status columns (no schema change needed beyond existing status).
    }

    public function down(): void
    {
        if (Schema::hasTable('student_lifecycle_requests')) {
            Schema::table('student_lifecycle_requests', function (Blueprint $table) {
                foreach ([
                    'deferment_months',
                    'attachments',
                    'registrar_status',
                    'dean_status',
                    'registrar_notes',
                    'dean_notes',
                    'registrar_reviewed_by_user_id',
                    'dean_reviewed_by_user_id',
                    'registrar_reviewed_at',
                    'dean_reviewed_at',
                ] as $column) {
                    if (Schema::hasColumn('student_lifecycle_requests', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
