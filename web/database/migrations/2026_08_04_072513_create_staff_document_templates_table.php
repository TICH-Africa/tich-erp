<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_document_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name', 200);
            $table->string('type', 100);
            $table->text('content');
            $table->json('variables')->nullable();
            $table->tinyInteger('is_active')->default(1);
            $table->unsignedBigInteger('created_by');
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->nullable()->useCurrentOnUpdate();

            $table->foreign('created_by')->references('id')->on('staff')->restrictOnDelete();
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_document_templates');
    }
};
