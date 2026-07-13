<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('role_name', 100)->unique();
            $table->string('role_category', 50); // executive, academic, administrative, teaching, student
            $table->text('description')->nullable();
            $table->tinyInteger('is_system_role')->default(0);
            $table->dateTime('created_at')->useCurrent();
        });

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('user_type', 50); // student, staff, admin, external
            $table->string('username', 100)->unique();
            $table->string('email', 255)->unique();
            $table->string('password_hash', 255);
            $table->unsignedBigInteger('staff_id')->nullable();
            $table->unsignedBigInteger('student_id')->nullable();
            $table->tinyInteger('mfa_enabled')->default(0);
            $table->string('mfa_method', 50)->nullable(); // sms, email, auth_app, whatsapp
            $table->tinyInteger('is_active')->default(1);
            $table->dateTime('last_login_at')->nullable();
            $table->integer('failed_login_attempts')->default(0);
            $table->dateTime('locked_until')->nullable();
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->nullable()->useCurrentOnUpdate();
            $table->unsignedBigInteger('created_by')->nullable();
        });

        Schema::create('user_roles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('role_id');
            $table->unsignedBigInteger('campus_id')->nullable();
            $table->unsignedBigInteger('department_id')->nullable();
            $table->dateTime('assigned_at')->useCurrent();
            $table->unsignedBigInteger('assigned_by')->nullable();
            $table->dateTime('expires_at')->nullable();
            $table->unique(['user_id', 'role_id', 'campus_id', 'department_id'], 'user_roles_unique');
            $table->foreign('user_id')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('role_id')->references('id')->on('roles')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_roles');
        Schema::dropIfExists('users');
        Schema::dropIfExists('roles');
    }
};
