<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lesson_plans', function (Blueprint $table) {
            if (! Schema::hasColumn('lesson_plans', 'source_type')) {
                $table->string('source_type', 20)->default('form')->after('prepared_by');
            }
            if (! Schema::hasColumn('lesson_plans', 'lesson_title')) {
                $table->string('lesson_title', 255)->nullable()->after('source_type');
            }
            if (! Schema::hasColumn('lesson_plans', 'uploaded_file_path')) {
                $table->string('uploaded_file_path', 500)->nullable()->after('resources_required');
            }
            if (! Schema::hasColumn('lesson_plans', 'uploaded_file_name')) {
                $table->string('uploaded_file_name', 255)->nullable()->after('uploaded_file_path');
            }
            if (! Schema::hasColumn('lesson_plans', 'form_payload')) {
                $table->json('form_payload')->nullable()->after('uploaded_file_name');
            }
            if (! Schema::hasColumn('lesson_plans', 'tutor_verified_at')) {
                $table->dateTime('tutor_verified_at')->nullable()->after('form_payload');
            }
        });
    }

    public function down(): void
    {
        Schema::table('lesson_plans', function (Blueprint $table) {
            foreach ([
                'source_type',
                'lesson_title',
                'uploaded_file_path',
                'uploaded_file_name',
                'form_payload',
                'tutor_verified_at',
            ] as $column) {
                if (Schema::hasColumn('lesson_plans', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
