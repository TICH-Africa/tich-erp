<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Drop legacy DB permission catalog tables. Runtime RBAC uses config catalogs;
 * only thin `roles` + `user_roles` remain for assignments.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('user_permissions');
        Schema::dropIfExists('role_permissions');
        Schema::dropIfExists('permissions');
    }

    public function down(): void
    {
        // Intentionally empty - permission tables are not restored.
    }
};
