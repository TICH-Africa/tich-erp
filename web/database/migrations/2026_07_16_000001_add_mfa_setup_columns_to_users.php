<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'mfa_secret_temp')) {
                $table->string('mfa_secret_temp', 100)->nullable()->after('mfa_secret');
            }
            if (! Schema::hasColumn('users', 'mfa_verified')) {
                $table->tinyInteger('mfa_verified')->default(0)->after('mfa_method');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'mfa_secret_temp')) {
                $table->dropColumn('mfa_secret_temp');
            }
            if (Schema::hasColumn('users', 'mfa_verified')) {
                $table->dropColumn('mfa_verified');
            }
        });
    }
};
