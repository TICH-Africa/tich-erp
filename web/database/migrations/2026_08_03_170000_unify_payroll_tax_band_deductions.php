<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_deduction_types', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('label', 120);
            $table->enum('value_type', ['band_percent', 'global_fixed'])->default('band_percent');
            $table->decimal('fixed_amount', 12, 2)->nullable();
            $table->decimal('employer_rate_percent', 7, 4)->nullable();
            $table->tinyInteger('reduces_taxable')->default(0);
            $table->unsignedSmallInteger('display_order')->default(0);
            $table->tinyInteger('is_active')->default(1);
            $table->timestamps();
        });

        Schema::create('payroll_band_deduction_rates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('payroll_tax_band_id');
            $table->unsignedBigInteger('payroll_deduction_type_id');
            $table->decimal('rate_percent', 7, 4)->nullable();
            $table->timestamps();

            $table->unique(['payroll_tax_band_id', 'payroll_deduction_type_id'], 'payroll_band_deduction_unique');
            $table->foreign('payroll_tax_band_id')->references('id')->on('payroll_tax_bands')->cascadeOnDelete();
            $table->foreign('payroll_deduction_type_id')->references('id')->on('payroll_deduction_types')->cascadeOnDelete();
        });

        $this->migrateLegacyStatutoryRates();
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_band_deduction_rates');
        Schema::dropIfExists('payroll_deduction_types');
    }

    private function migrateLegacyStatutoryRates(): void
    {
        if (! Schema::hasTable('payroll_statutory_rates') || ! Schema::hasTable('payroll_tax_bands')) {
            return;
        }

        if (DB::table('payroll_deduction_types')->exists()) {
            return;
        }

        $legacyRates = DB::table('payroll_statutory_rates')->orderBy('display_order')->get();
        $bands = DB::table('payroll_tax_bands')->orderBy('display_order')->orderBy('min_amount')->get();

        if ($legacyRates->isEmpty()) {
            $this->seedDefaultDeductionTypes($bands);

            return;
        }

        $typeMap = [];
        $now = now();

        foreach ($legacyRates as $rate) {
            if ($rate->code === 'nssf_tier2') {
                continue;
            }

            if ($rate->code === 'nssf_tier1') {
                $typeId = DB::table('payroll_deduction_types')->insertGetId([
                    'code' => 'nssf',
                    'label' => 'NSSF',
                    'value_type' => 'band_percent',
                    'employer_rate_percent' => $rate->employer_rate_percent,
                    'reduces_taxable' => 1,
                    'display_order' => 2,
                    'is_active' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                $typeMap['nssf'] = $typeId;

                continue;
            }

            $valueType = $rate->fixed_amount !== null ? 'global_fixed' : 'band_percent';

            $typeId = DB::table('payroll_deduction_types')->insertGetId([
                'code' => $rate->code,
                'label' => $rate->label,
                'value_type' => $valueType,
                'fixed_amount' => $rate->fixed_amount,
                'employer_rate_percent' => $rate->employer_rate_percent,
                'reduces_taxable' => in_array($rate->code, ['pension_employee'], true) ? 1 : 0,
                'display_order' => $rate->display_order,
                'is_active' => $rate->is_active,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $typeMap[$rate->code] = $typeId;
        }

        foreach ($bands as $band) {
            foreach ($legacyRates as $rate) {
                if ($rate->code === 'personal_relief' || $rate->code === 'nssf_tier2') {
                    continue;
                }

                if ($rate->code === 'nssf_tier1') {
                    $ratePercent = $this->nssfRateForBand($band, $legacyRates);

                    if ($ratePercent === null) {
                        continue;
                    }

                    DB::table('payroll_band_deduction_rates')->insert([
                        'payroll_tax_band_id' => $band->id,
                        'payroll_deduction_type_id' => $typeMap['nssf'],
                        'rate_percent' => $ratePercent,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);

                    continue;
                }

                if ($rate->fixed_amount !== null) {
                    continue;
                }

                DB::table('payroll_band_deduction_rates')->insert([
                    'payroll_tax_band_id' => $band->id,
                    'payroll_deduction_type_id' => $typeMap[$rate->code],
                    'rate_percent' => $rate->rate_percent,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    private function nssfRateForBand(object $band, $legacyRates): ?float
    {
        $tier1 = $legacyRates->firstWhere('code', 'nssf_tier1');
        $tier2 = $legacyRates->firstWhere('code', 'nssf_tier2');

        if (! $tier1) {
            return null;
        }

        $bandMax = $band->max_amount !== null ? (float) $band->max_amount : PHP_FLOAT_MAX;
        $bandMin = (float) $band->min_amount;

        if ($tier2 && $bandMin >= (float) ($tier2->floor_amount ?? 0)) {
            return (float) $tier2->rate_percent;
        }

        if ($bandMax <= (float) ($tier1->ceiling_amount ?? 0) || $bandMin < (float) ($tier2->floor_amount ?? PHP_FLOAT_MAX)) {
            return (float) $tier1->rate_percent;
        }

        return (float) $tier1->rate_percent;
    }

    private function seedDefaultDeductionTypes($bands): void
    {
        $now = now();
        $types = [
            ['code' => 'personal_relief', 'label' => 'Personal relief', 'value_type' => 'global_fixed', 'fixed_amount' => 2400, 'display_order' => 1],
            ['code' => 'nssf', 'label' => 'NSSF', 'value_type' => 'band_percent', 'employer_rate_percent' => 6, 'reduces_taxable' => 1, 'display_order' => 2],
            ['code' => 'sha', 'label' => 'SHA / SHIF', 'value_type' => 'band_percent', 'display_order' => 3],
            ['code' => 'ahl', 'label' => 'Housing Levy', 'value_type' => 'band_percent', 'employer_rate_percent' => 1.5, 'display_order' => 4],
        ];

        foreach ($types as $type) {
            $typeId = DB::table('payroll_deduction_types')->insertGetId([
                ...$type,
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            if ($type['value_type'] === 'band_percent') {
                foreach ($bands as $band) {
                    DB::table('payroll_band_deduction_rates')->insert([
                        'payroll_tax_band_id' => $band->id,
                        'payroll_deduction_type_id' => $typeId,
                        'rate_percent' => match ($type['code']) {
                            'nssf' => 6,
                            'sha' => 2.75,
                            'ahl' => 1.5,
                            default => 0,
                        },
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }
        }
    }
};
