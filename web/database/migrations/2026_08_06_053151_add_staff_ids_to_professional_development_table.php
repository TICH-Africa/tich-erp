<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('professional_development', function (Blueprint $table) {
            $table->json('staff_ids')->nullable()->after('staff_id');
        });
    }

    public function down(): void
    {
        Schema::table('professional_development', function (Blueprint $table) {
            $table->dropColumn('staff_ids');
        });
    }
};