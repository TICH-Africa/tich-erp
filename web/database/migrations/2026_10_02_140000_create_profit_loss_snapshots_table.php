<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('profit_loss_snapshots')) {
            return;
        }

        Schema::create('profit_loss_snapshots', function (Blueprint $table) {
            $table->id();
            $table->string('label', 200);
            $table->string('period_preset', 40)->default('custom');
            $table->date('period_from');
            $table->date('period_to');
            $table->string('view_mode', 20)->default('standard');
            $table->decimal('total_revenue', 18, 2)->default(0);
            $table->decimal('total_expenses', 18, 2)->default(0);
            $table->decimal('net_income', 18, 2)->default(0);
            $table->json('payload');
            $table->unsignedBigInteger('saved_by')->nullable();
            $table->timestamps();

            $table->index(['period_from', 'period_to']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profit_loss_snapshots');
    }
};
