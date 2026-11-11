<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('research_projects', function (Blueprint $table) {
            $table->string('project_url', 500)->nullable()->after('summary');
            $table->text('objective')->nullable()->after('project_url');
        });
    }

    public function down(): void
    {
        Schema::table('research_projects', function (Blueprint $table) {
            $table->dropColumn(['project_url', 'objective']);
        });
    }
};
