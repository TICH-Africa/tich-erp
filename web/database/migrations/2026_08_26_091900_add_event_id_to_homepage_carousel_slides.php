<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('homepage_carousel_slides')) {
            return;
        }

        Schema::table('homepage_carousel_slides', function (Blueprint $table) {
            if (! Schema::hasColumn('homepage_carousel_slides', 'event_id')) {
                $table->unsignedBigInteger('event_id')->nullable()->after('program_id');
                $table->unique('event_id');
                if (Schema::hasTable('events')) {
                    $table->foreign('event_id')->references('id')->on('events')->nullOnDelete();
                }
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('homepage_carousel_slides')) {
            return;
        }

        Schema::table('homepage_carousel_slides', function (Blueprint $table) {
            if (Schema::hasColumn('homepage_carousel_slides', 'event_id')) {
                try {
                    $table->dropForeign(['event_id']);
                } catch (\Throwable) {
                    //
                }
                try {
                    $table->dropUnique(['event_id']);
                } catch (\Throwable) {
                    //
                }
                $table->dropColumn('event_id');
            }
        });
    }
};
