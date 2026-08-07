<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hr_policies', function (Blueprint $table) {
            if (! Schema::hasColumn('hr_policies', 'version')) {
                $table->string('version', 20)->default('1.0')->after('slug');
            }
        });

        Schema::table('policy_acknowledgements', function (Blueprint $table) {
            if (! Schema::hasColumn('policy_acknowledgements', 'policy_id')) {
                $table->foreignId('policy_id')->nullable()->after('id')->constrained('hr_policies')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('policy_acknowledgements', function (Blueprint $table) {
            if (Schema::hasColumn('policy_acknowledgements', 'policy_id')) {
                $table->dropForeign(['policy_id']);
                $table->dropColumn('policy_id');
            }
        });

        Schema::table('hr_policies', function (Blueprint $table) {
            if (Schema::hasColumn('hr_policies', 'version')) {
                $table->dropColumn('version');
            }
        });
    }
};
