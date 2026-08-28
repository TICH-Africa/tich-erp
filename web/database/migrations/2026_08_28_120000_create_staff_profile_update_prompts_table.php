<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_profile_update_prompts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_id')->constrained('staff')->cascadeOnDelete();
            $table->foreignId('requested_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('requested_via_module', 32)->default('hr');
            $table->json('requested_fields');
            $table->text('notes')->nullable();
            $table->string('token', 64)->unique();
            $table->string('status', 32)->default('pending');
            $table->timestamp('emailed_at')->nullable();
            $table->timestamp('fulfilled_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['staff_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_profile_update_prompts');
    }
};
