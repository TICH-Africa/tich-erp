<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('unit_contents', function (Blueprint $table) {
            $table->string('section_title', 300)->nullable()->after('title');
            $table->unsignedSmallInteger('lesson_number')->nullable()->after('section_title');
        });
    }

    public function down(): void
    {
        Schema::table('unit_contents', function (Blueprint $table) {
            $table->dropColumn(['section_title', 'lesson_number']);
        });
    }
};
