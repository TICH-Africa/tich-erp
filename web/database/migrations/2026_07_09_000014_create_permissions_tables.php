<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('permission_name', 100)->unique();
            $table->string('slug', 100)->unique();
            $table->string('module', 50); // core, admin, academics, finance, hr, portal, qa, notifications, donations, newsletter, site_settings
            $table->string('category', 50); // view, create, edit, delete, approve, reject, export, import, manage, audit
            $table->text('description')->nullable();
            $table->tinyInteger('is_system')->default(0);
            $table->dateTime('created_at')->useCurrent();
        });

        Schema::create('role_permissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('role_id');
            $table->unsignedBigInteger('permission_id');
            $table->dateTime('granted_at')->useCurrent();
            $table->unsignedBigInteger('granted_by')->nullable();
            $table->unique(['role_id', 'permission_id']);
            $table->foreign('role_id')->references('id')->on('roles')->restrictOnDelete();
            $table->foreign('permission_id')->references('id')->on('permissions')->restrictOnDelete();
        });

        Schema::create('user_permissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('permission_id');
            $table->unsignedBigInteger('campus_id')->nullable();
            $table->unsignedBigInteger('department_id')->nullable();
            $table->dateTime('granted_at')->useCurrent();
            $table->unsignedBigInteger('granted_by')->nullable();
            $table->dateTime('expires_at')->nullable();
            $table->unique(['user_id', 'permission_id', 'campus_id', 'department_id'], 'user_permissions_unique');
            $table->foreign('user_id')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('permission_id')->references('id')->on('permissions')->restrictOnDelete();
        });

        // Add MFA secret and backup codes to users table
        Schema::table('users', function (Blueprint $table) {
            $table->string('mfa_secret', 100)->nullable();
            $table->json('mfa_backup_codes')->nullable();
            $table->dateTime('mfa_enabled_at')->nullable();
            $table->dateTime('mfa_last_verified_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['mfa_secret', 'mfa_backup_codes', 'mfa_enabled_at', 'mfa_last_verified_at']);
        });

        Schema::dropIfExists('user_permissions');
        Schema::dropIfExists('role_permissions');
        Schema::dropIfExists('permissions');
    }
};
