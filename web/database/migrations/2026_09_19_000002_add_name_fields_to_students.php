<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('students')) {
            Schema::table('students', function (Blueprint $table) {
                if (! Schema::hasColumn('students', 'first_name')) {
                    $table->string('first_name', 100)->after('application_id');
                }
                if (! Schema::hasColumn('students', 'middle_name')) {
                    $table->string('middle_name', 100)->nullable()->after('first_name');
                }
                if (! Schema::hasColumn('students', 'surname')) {
                    $table->string('surname', 100)->after('middle_name');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('students')) {
            Schema::table('students', function (Blueprint $table) {
                foreach (['first_name', 'middle_name', 'surname'] as $column) {
                    if (Schema::hasColumn('students', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};