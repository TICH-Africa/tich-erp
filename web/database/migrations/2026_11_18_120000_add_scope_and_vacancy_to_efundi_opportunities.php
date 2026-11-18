<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('efundi_opportunities', function (Blueprint $table) {
            $table->string('opportunity_scope', 20)->default('external')->after('opportunity_type');
            $table->foreignId('job_vacancy_id')->nullable()->after('opportunity_scope')->constrained('job_vacancies')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('efundi_opportunities', function (Blueprint $table) {
            $table->dropConstrainedForeignId('job_vacancy_id');
            $table->dropColumn('opportunity_scope');
        });
    }
};