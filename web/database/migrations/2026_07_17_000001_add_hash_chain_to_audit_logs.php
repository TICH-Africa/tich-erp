<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            if (! Schema::hasColumn('audit_logs', 'status')) {
                $table->string('status', 50)->default('success');
            }
            if (! Schema::hasColumn('audit_logs', 'module')) {
                $table->string('module', 50)->nullable();
            }
            if (! Schema::hasColumn('audit_logs', 'previous_hash')) {
                $table->string('previous_hash', 64)->nullable();
            }
            if (! Schema::hasColumn('audit_logs', 'record_hash')) {
                $table->string('record_hash', 64)->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            foreach (['record_hash', 'previous_hash', 'module', 'status'] as $column) {
                if (Schema::hasColumn('audit_logs', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
