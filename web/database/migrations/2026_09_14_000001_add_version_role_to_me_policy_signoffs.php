<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('me_policy_signoffs', function (Blueprint $table) {
            $table->string('policy_version', 50)->nullable()->after('policy_id');
            $table->string('signed_role', 100)->nullable()->after('policy_version');
        });
    }

    public function down(): void
    {
        Schema::table('me_policy_signoffs', function (Blueprint $table) {
            $table->dropColumn(['policy_version', 'signed_role']);
        });
    }
};