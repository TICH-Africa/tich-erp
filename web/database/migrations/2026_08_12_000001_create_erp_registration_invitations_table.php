<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('erp_registration_invitations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('staff_id');
            $table->string('email', 255);
            $table->string('token', 64)->unique();
            $table->string('sent_via_module', 20);
            $table->unsignedBigInteger('invited_by');
            $table->dateTime('expires_at');
            $table->dateTime('used_at')->nullable();
            $table->dateTime('created_at')->useCurrent();

            $table->foreign('staff_id')->references('id')->on('staff')->restrictOnDelete();
            $table->foreign('invited_by')->references('id')->on('users')->restrictOnDelete();
            $table->index(['email', 'used_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('erp_registration_invitations');
    }
};
