<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('erp_registration_invitations', function (Blueprint $table) {
            $table->dropForeign(['staff_id']);
        });

        Schema::table('erp_registration_invitations', function (Blueprint $table) {
            $table->unsignedBigInteger('staff_id')->nullable()->change();
            $table->foreign('staff_id')->references('id')->on('staff')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('erp_registration_invitations', function (Blueprint $table) {
            $table->dropForeign(['staff_id']);
        });

        Schema::table('erp_registration_invitations', function (Blueprint $table) {
            $table->unsignedBigInteger('staff_id')->nullable(false)->change();
            $table->foreign('staff_id')->references('id')->on('staff')->restrictOnDelete();
        });
    }
};
