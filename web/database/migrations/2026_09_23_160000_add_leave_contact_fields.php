<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('leave_requests')) {
            return;
        }

        Schema::table('leave_requests', function (Blueprint $table) {
            if (! Schema::hasColumn('leave_requests', 'contact_mobile')) {
                $table->string('contact_mobile', 40)->nullable()->after('handover_notes');
            }
            if (! Schema::hasColumn('leave_requests', 'contact_email')) {
                $table->string('contact_email', 191)->nullable()->after('contact_mobile');
            }
            if (! Schema::hasColumn('leave_requests', 'contact_postal_address')) {
                $table->string('contact_postal_address', 255)->nullable()->after('contact_email');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('leave_requests')) {
            return;
        }

        Schema::table('leave_requests', function (Blueprint $table) {
            foreach (['contact_mobile', 'contact_email', 'contact_postal_address'] as $col) {
                if (Schema::hasColumn('leave_requests', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
