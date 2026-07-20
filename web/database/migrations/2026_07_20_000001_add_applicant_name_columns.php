<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('applicants')) {
            return;
        }

        Schema::table('applicants', function (Blueprint $table) {
            if (! Schema::hasColumn('applicants', 'middle_name')) {
                $table->string('middle_name', 100)->nullable()->after('first_name');
            }

            if (! Schema::hasColumn('applicants', 'academic_review_status')) {
                $table->string('academic_review_status', 50)->default('pending')->after('status');
            }

            if (! Schema::hasColumn('applicants', 'application_source')) {
                $table->string('application_source', 50)->default('online')->after('academic_review_status');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('applicants')) {
            return;
        }

        Schema::table('applicants', function (Blueprint $table) {
            if (Schema::hasColumn('applicants', 'middle_name')) {
                $table->dropColumn('middle_name');
            }
        });
    }
};
