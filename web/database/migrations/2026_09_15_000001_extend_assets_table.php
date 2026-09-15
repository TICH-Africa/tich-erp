<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->string('tag_number', 50)->unique()->nullable()->after('asset_number');
            $table->string('qr_code_path', 500)->nullable()->after('tag_number');
            $table->foreignId('custodian_id')->nullable()->after('qr_code_path')->constrained('staff')->nullOnDelete();
            $table->string('location_name', 255)->nullable()->after('custodian_id');
            $table->string('building', 100)->nullable()->after('location_name');
            $table->string('room', 100)->nullable()->after('building');
            $table->string('asset_status', 50)->default('new')->after('room');
            $table->date('handover_date')->nullable()->after('asset_status');
            $table->foreignId('grn_id')->nullable()->after('handover_date')->constrained('goods_received_notes')->nullOnDelete();
            $table->foreignId('procurement_requisition_id')->nullable()->after('grn_id')->constrained('procurement_requisitions')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->dropForeign(['custodian_id']);
            $table->dropForeign(['grn_id']);
            $table->dropForeign(['procurement_requisition_id']);
            $table->dropColumn([
                'tag_number',
                'qr_code_path',
                'custodian_id',
                'location_name',
                'building',
                'room',
                'asset_status',
                'handover_date',
                'grn_id',
                'procurement_requisition_id',
            ]);
        });
    }
};
