<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('staff_attendance', function (Blueprint $table) {
            $table->decimal('clock_in_latitude', 10, 7)->nullable()->after('location_lat_long');
            $table->decimal('clock_in_longitude', 10, 7)->nullable()->after('clock_in_latitude');
            $table->decimal('clock_in_accuracy_m', 8, 2)->nullable()->after('clock_in_longitude');
            $table->string('location_verification_status', 30)->nullable()->after('clock_in_accuracy_m');
        });
    }

    public function down(): void
    {
        Schema::table('staff_attendance', function (Blueprint $table) {
            $table->dropColumn([
                'clock_in_latitude',
                'clock_in_longitude',
                'clock_in_accuracy_m',
                'location_verification_status',
            ]);
        });
    }
};
