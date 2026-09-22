<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('qca_milestones');
        Schema::dropIfExists('qca_flags');
        Schema::dropIfExists('qa_corrective_actions');
    }

    public function down(): void
    {
        // Tables retired; recreate from historical migrations if needed.
    }
};
