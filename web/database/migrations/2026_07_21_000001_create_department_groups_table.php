<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('department_groups', function (Blueprint $table) {
            $table->id();
            $table->string('group_code', 20)->unique();
            $table->string('group_name', 200);
            $table->unsignedSmallInteger('display_order')->default(0);
            $table->tinyInteger('is_active')->default(1);
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->nullable()->useCurrentOnUpdate();
            $table->unsignedBigInteger('created_by')->nullable();
        });

        Schema::table('departments', function (Blueprint $table) {
            if (! Schema::hasColumn('departments', 'department_group_id')) {
                $table->unsignedBigInteger('department_group_id')->nullable()->after('dept_category');
                $table->foreign('department_group_id')->references('id')->on('department_groups')->nullOnDelete();
            }
            if (! Schema::hasColumn('departments', 'display_order')) {
                $table->unsignedSmallInteger('display_order')->default(0)->after('department_group_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            if (Schema::hasColumn('departments', 'department_group_id')) {
                $table->dropForeign(['department_group_id']);
                $table->dropColumn('department_group_id');
            }
            if (Schema::hasColumn('departments', 'display_order')) {
                $table->dropColumn('display_order');
            }
        });

        Schema::dropIfExists('department_groups');
    }
};
