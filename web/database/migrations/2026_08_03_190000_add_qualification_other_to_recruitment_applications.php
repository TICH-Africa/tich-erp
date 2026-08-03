<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recruitment_applications', function (Blueprint $table) {
            if (! Schema::hasColumn('recruitment_applications', 'qualification_other')) {
                $table->string('qualification_other', 200)->nullable()->after('highest_qualification');
            }
        });
    }

    public function down(): void
    {
        Schema::table('recruitment_applications', function (Blueprint $table) {
            if (Schema::hasColumn('recruitment_applications', 'qualification_other')) {
                $table->dropColumn('qualification_other');
            }
        });
    }
};
