<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('staff', function (Blueprint $table) {
            $table->timestamp('archived_at')->nullable()->after('exit_date');
            $table->foreignId('archived_by')->nullable()->constrained('staff')->nullOnDelete()->after('archived_at');
            $table->text('archive_reason')->nullable()->after('archived_by');
        });
    }

    public function down(): void
    {
        Schema::table('staff', function (Blueprint $table) {
            $table->dropForeign(['archived_by']);
            $table->dropColumn(['archived_at', 'archived_by', 'archive_reason']);
        });
    }
};