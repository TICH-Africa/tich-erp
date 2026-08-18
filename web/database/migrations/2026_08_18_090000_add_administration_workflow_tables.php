<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('admin_budget_requests', 'is_late')) {
            Schema::table('admin_budget_requests', function (Blueprint $table) {
                $table->boolean('is_late')->default(false)->after('submitted_at');
                $table->dateTime('deadline_at')->nullable()->after('is_late');
            });
        }

        Schema::create('admin_calendar_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('fiscal_year');
            $table->string('event_type', 40);
            $table->string('title', 300);
            $table->date('starts_on');
            $table->date('ends_on')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->index(['fiscal_year', 'event_type', 'starts_on']);
        });

        Schema::create('admin_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('planning_cycle_id')->nullable()->constrained('admin_planning_cycles')->nullOnDelete();
            $table->foreignId('department_id')->constrained('departments')->cascadeOnDelete();
            $table->string('title', 300);
            $table->text('description')->nullable();
            $table->unsignedBigInteger('owner_id')->nullable();
            $table->date('due_on');
            $table->string('status', 30)->default('pending');
            $table->decimal('budget_implication', 14, 2)->default(0);
            $table->timestamps();
            $table->index(['department_id', 'due_on', 'status']);
        });

        Schema::create('admin_variances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('planning_cycle_id')->nullable()->constrained('admin_planning_cycles')->nullOnDelete();
            $table->foreignId('department_id')->constrained('departments')->cascadeOnDelete();
            $table->unsignedSmallInteger('fiscal_year');
            $table->unsignedTinyInteger('month');
            $table->decimal('planned_amount', 14, 2)->default(0);
            $table->decimal('actual_amount', 14, 2)->default(0);
            $table->text('explanation')->nullable();
            $table->text('lessons')->nullable();
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->timestamps();
            $table->index(['department_id', 'fiscal_year', 'month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_variances');
        Schema::dropIfExists('admin_tasks');
        Schema::dropIfExists('admin_calendar_events');
        if (Schema::hasColumn('admin_budget_requests', 'is_late')) {
            Schema::table('admin_budget_requests', function (Blueprint $table) {
                $table->dropColumn(['is_late', 'deadline_at']);
            });
        }
    }
};