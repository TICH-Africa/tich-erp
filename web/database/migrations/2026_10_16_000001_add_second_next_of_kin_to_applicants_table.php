<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('applicants')) {
            return;
        }

        Schema::table('applicants', function (Blueprint $table) {
            if (! Schema::hasColumn('applicants', 'next_of_kin2_name')) {
                $table->string('next_of_kin2_name', 300)->nullable();
            }
            if (! Schema::hasColumn('applicants', 'next_of_kin2_relationship')) {
                $table->string('next_of_kin2_relationship', 50)->nullable();
            }
            if (! Schema::hasColumn('applicants', 'next_of_kin2_phone')) {
                $table->string('next_of_kin2_phone', 30)->nullable();
            }
            if (! Schema::hasColumn('applicants', 'next_of_kin2_address')) {
                $table->string('next_of_kin2_address', 500)->nullable();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('applicants')) {
            return;
        }

        Schema::table('applicants', function (Blueprint $table) {
            foreach (['next_of_kin2_name', 'next_of_kin2_relationship', 'next_of_kin2_phone', 'next_of_kin2_address'] as $column) {
                if (Schema::hasColumn('applicants', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
