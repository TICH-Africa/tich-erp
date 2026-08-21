<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('admin_statutory_certifications')) {
            return;
        }

        Schema::table('admin_statutory_certifications', function (Blueprint $table) {
            $table->string('authority', 255)->change();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('admin_statutory_certifications')) {
            return;
        }

        Schema::table('admin_statutory_certifications', function (Blueprint $table) {
            $table->string('authority', 50)->change();
        });
    }
};
