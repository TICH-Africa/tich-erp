<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_policies', function (Blueprint $table) {
            $table->id();
            $table->string('fiscal_year', 20);
            $table->string('title', 300);
            $table->string('version', 50)->nullable();
            $table->string('file_path', 500);
            $table->text('description')->nullable();
            $table->date('effective_date')->nullable();
            $table->string('status', 50)->default('draft'); // draft, published, archived
            $table->unsignedBigInteger('uploaded_by')->nullable();
            $table->dateTime('uploaded_at')->useCurrent();
            $table->dateTime('published_at')->nullable();
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->nullable()->useCurrentOnUpdate();
            $table->foreign('uploaded_by')->references('id')->on('staff')->nullOnDelete();
            $table->index(['fiscal_year', 'status'], 'finance_policies_year_status_idx');
        });

        Schema::create('finance_policy_signoffs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('policy_id');
            $table->string('policy_version', 50)->nullable();
            $table->string('signed_role', 100)->nullable();
            $table->unsignedBigInteger('department_id');
            $table->unsignedBigInteger('staff_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('signed_name', 200);
            $table->string('employee_number', 100)->nullable();
            $table->text('signature')->nullable();
            $table->string('ip_address', 64)->nullable();
            $table->dateTime('signed_at')->useCurrent();
            $table->foreign('policy_id')->references('id')->on('finance_policies')->restrictOnDelete();
            $table->foreign('department_id')->references('id')->on('departments')->restrictOnDelete();
            $table->foreign('staff_id')->references('id')->on('staff')->restrictOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->unique(['policy_id', 'department_id', 'staff_id'], 'finance_policy_signoffs_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_policy_signoffs');
        Schema::dropIfExists('finance_policies');
    }
};
