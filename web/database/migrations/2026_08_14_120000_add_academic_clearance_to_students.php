<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('students')) {
            return;
        }

        Schema::table('students', function (Blueprint $table) {
            if (! Schema::hasColumn('students', 'academic_clearance_status')) {
                $table->string('academic_clearance_status', 50)->default('pending')->after('fee_clearance_status');
            }
            if (! Schema::hasColumn('students', 'academically_cleared_at')) {
                $table->dateTime('academically_cleared_at')->nullable()->after('academic_clearance_status');
            }
            if (! Schema::hasColumn('students', 'academically_cleared_by')) {
                $table->unsignedBigInteger('academically_cleared_by')->nullable()->after('academically_cleared_at');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('students')) {
            return;
        }

        Schema::table('students', function (Blueprint $table) {
            foreach (['academically_cleared_by', 'academically_cleared_at', 'academic_clearance_status'] as $column) {
                if (Schema::hasColumn('students', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
