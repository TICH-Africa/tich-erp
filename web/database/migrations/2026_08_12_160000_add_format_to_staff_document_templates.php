<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('staff_document_templates', 'format')) {
            Schema::table('staff_document_templates', function (Blueprint $table) {
                $table->string('format', 20)->default('html')->after('content')
                    ->comment('html or docx');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('staff_document_templates', 'format')) {
            Schema::table('staff_document_templates', function (Blueprint $table) {
                $table->dropColumn('format');
            });
        }
    }
};
