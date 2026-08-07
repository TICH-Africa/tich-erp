<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('policy_acknowledgements', function (Blueprint $table) {
            if (! Schema::hasColumn('policy_acknowledgements', 'updated_at')) {
                $table->dateTime('updated_at')->nullable()->after('created_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('policy_acknowledgements', function (Blueprint $table) {
            if (Schema::hasColumn('policy_acknowledgements', 'updated_at')) {
                $table->dropColumn('updated_at');
            }
        });
    }
};
