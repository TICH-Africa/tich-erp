<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('qa_plans', function (Blueprint $table) {
            if (! Schema::hasColumn('qa_plans', 'description')) {
                $table->text('description')->nullable()->after('plan_name');
            }
            if (! Schema::hasColumn('qa_plans', 'instructions')) {
                $table->text('instructions')->nullable()->after('description');
            }
            if (! Schema::hasColumn('qa_plans', 'due_at')) {
                $table->dateTime('due_at')->nullable()->after('period_end');
            }
            if (! Schema::hasColumn('qa_plans', 'pass_threshold')) {
                $table->decimal('pass_threshold', 5, 2)->default(70.00)->after('due_at');
            }
            if (! Schema::hasColumn('qa_plans', 'dispatched_at')) {
                $table->dateTime('dispatched_at')->nullable()->after('deployed_at');
            }
            if (! Schema::hasColumn('qa_plans', 'compiled_at')) {
                $table->dateTime('compiled_at')->nullable()->after('dispatched_at');
            }
            if (! Schema::hasColumn('qa_plans', 'created_by')) {
                $table->unsignedBigInteger('created_by')->nullable()->after('compiled_at');
                $table->foreign('created_by')->references('id')->on('staff')->nullOnDelete();
            }
        });

        // Allow draft plans before staff deploy (deployed_by/deployed_at become set on dispatch).
        Schema::table('qa_plans', function (Blueprint $table) {
            $table->unsignedBigInteger('deployed_by')->nullable()->change();
            $table->dateTime('deployed_at')->nullable()->change();
            $table->string('status', 50)->default('draft')->change();
        });

        if (! Schema::hasTable('qa_capacity_sessions')) {
            Schema::create('qa_capacity_sessions', function (Blueprint $table) {
                $table->id();
                $table->string('title', 300);
                $table->text('description')->nullable();
                $table->dateTime('scheduled_at')->nullable();
                $table->string('audience', 300)->nullable();
                $table->string('location', 300)->nullable();
                $table->string('status', 50)->default('scheduled'); // scheduled, completed, cancelled
                $table->unsignedBigInteger('created_by')->nullable();
                $table->dateTime('created_at')->useCurrent();
                $table->dateTime('updated_at')->nullable()->useCurrentOnUpdate();
                $table->foreign('created_by')->references('id')->on('staff')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('qa_capacity_sessions');

        Schema::table('qa_plans', function (Blueprint $table) {
            foreach (['description', 'instructions', 'due_at', 'pass_threshold', 'dispatched_at', 'compiled_at', 'created_by'] as $column) {
                if (Schema::hasColumn('qa_plans', $column)) {
                    if ($column === 'created_by') {
                        $table->dropForeign(['created_by']);
                    }
                    $table->dropColumn($column);
                }
            }
        });
    }
};
