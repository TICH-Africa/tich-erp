<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('staff')) {
            return;
        }

        Schema::table('staff', function (Blueprint $table) {
            if (! Schema::hasColumn('staff', 'preferred_erp_email')) {
                // primary = personal; organisation = organisational/secondary
                $table->string('preferred_erp_email', 20)
                    ->default('primary')
                    ->after('organisation_email');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('staff') || ! Schema::hasColumn('staff', 'preferred_erp_email')) {
            return;
        }

        Schema::table('staff', function (Blueprint $table) {
            $table->dropColumn('preferred_erp_email');
        });
    }
};
