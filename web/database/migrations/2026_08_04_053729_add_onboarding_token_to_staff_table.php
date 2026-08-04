<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('staff', function (Blueprint $table) {
            $table->string('onboarding_token', 64)->nullable()->unique()->after('user_id');
            $table->dateTime('onboarding_token_expires_at')->nullable()->after('onboarding_token');
        });
    }

    public function down(): void
    {
        Schema::table('staff', function (Blueprint $table) {
            $table->dropColumn(['onboarding_token', 'onboarding_token_expires_at']);
        });
    }
};
