<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            if (! Schema::hasColumn('students', 'portal_invite_token')) {
                $table->string('portal_invite_token', 64)->nullable()->unique()->after('user_id');
            }

            if (! Schema::hasColumn('students', 'portal_invite_expires_at')) {
                $table->dateTime('portal_invite_expires_at')->nullable()->after('portal_invite_token');
            }

            if (! Schema::hasColumn('students', 'portal_activated_at')) {
                $table->dateTime('portal_activated_at')->nullable()->after('portal_invite_expires_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            foreach (['portal_invite_token', 'portal_invite_expires_at', 'portal_activated_at'] as $column) {
                if (Schema::hasColumn('students', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
