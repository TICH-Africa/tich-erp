<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('staff_documents', function (Blueprint $table) {
            $table->string('status', 50)->nullable()->default('pending')->after('is_verified');
            $table->unsignedBigInteger('approved_by')->nullable()->after('status');
            $table->unsignedBigInteger('rejected_by')->nullable()->after('approved_by');
            $table->dateTime('approved_at')->nullable()->after('rejected_by');
            $table->dateTime('rejected_at')->nullable()->after('approved_at');
            $table->text('rejection_reason')->nullable()->after('rejected_at');

            $table->foreign('approved_by')->references('id')->on('staff')->nullOnDelete();
            $table->foreign('rejected_by')->references('id')->on('staff')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('staff_documents', function (Blueprint $table) {
            $table->dropForeign(['approved_by']);
            $table->dropForeign(['rejected_by']);
            $table->dropColumn([
                'status',
                'approved_by',
                'rejected_by',
                'approved_at',
                'rejected_at',
                'rejection_reason',
            ]);
        });
    }
};