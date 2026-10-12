<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('policy_reads')) {
            return;
        }

        Schema::create('policy_reads', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('policy_id');
            $table->unsignedBigInteger('staff_id');
            $table->dateTime('read_at')->useCurrent();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();

            $table->unique(['policy_id', 'staff_id'], 'policy_reads_unique');
            $table->foreign('policy_id')->references('id')->on('hr_policies')->cascadeOnDelete();
            $table->foreign('staff_id')->references('id')->on('staff')->cascadeOnDelete();
            $table->index('read_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('policy_reads');
    }
};