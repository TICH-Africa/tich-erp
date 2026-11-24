<?php

use App\Models\OffboardingQuestionnaireTemplate;
use App\Support\EmploymentCategory;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('staff')
            ->whereIn('employment_category', EmploymentCategory::legacyWithholdingCodes())
            ->update(['employment_category' => 'consultancy']);

        DB::table('staff_contracts')
            ->whereIn('contract_type', EmploymentCategory::legacyWithholdingCodes())
            ->update(['contract_type' => 'consultancy']);

        OffboardingQuestionnaireTemplate::query()->each(function (OffboardingQuestionnaireTemplate $template): void {
            $categories = $template->employment_categories ?? [];
            if ($categories === []) {
                return;
            }

            $normalized = [];
            foreach ($categories as $code) {
                $code = EmploymentCategory::normalize((string) $code);
                if ($code !== null && $code !== '') {
                    $normalized[$code] = true;
                }
            }

            $merged = array_keys($normalized);
            sort($merged);

            if ($merged !== array_values($categories)) {
                $template->employment_categories = $merged;
                $template->save();
            }
        });
    }

    public function down(): void
    {
        // Legacy codes are not restored; data was consolidated intentionally.
    }
};