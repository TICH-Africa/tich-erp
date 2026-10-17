<?php

use App\Services\KenyaPublicHolidayService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('public_holidays')) {
            return;
        }

        app(KenyaPublicHolidayService::class)->syncConfiguredWindow();
    }

    public function down(): void
    {
        // Holiday rows may be referenced in reports; leave data in place on rollback.
    }
};
