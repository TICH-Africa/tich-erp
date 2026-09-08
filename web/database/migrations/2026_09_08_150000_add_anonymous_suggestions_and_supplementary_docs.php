<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('student_suggestions') && ! Schema::hasColumn('student_suggestions', 'is_anonymous')) {
            Schema::table('student_suggestions', function (Blueprint $table) {
                $table->boolean('is_anonymous')->default(false)->after('student_id');
            });
        }

        if (Schema::hasTable('supplementary_requests') && ! Schema::hasColumn('supplementary_requests', 'supporting_docs')) {
            Schema::table('supplementary_requests', function (Blueprint $table) {
                $table->json('supporting_docs')->nullable()->after('student_notes');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('student_suggestions') && Schema::hasColumn('student_suggestions', 'is_anonymous')) {
            Schema::table('student_suggestions', function (Blueprint $table) {
                $table->dropColumn('is_anonymous');
            });
        }

        if (Schema::hasTable('supplementary_requests') && Schema::hasColumn('supplementary_requests', 'supporting_docs')) {
            Schema::table('supplementary_requests', function (Blueprint $table) {
                $table->dropColumn('supporting_docs');
            });
        }
    }
};
