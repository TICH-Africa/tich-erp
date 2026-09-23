<?php

namespace App\Services\Leave;

use App\Models\LeaveType;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Hard-coded leave rules. leave_types is a thin synced identity table for FKs.
 */
class LeaveCatalogService
{
    /**
     * @return array<string, array<string, mixed>>
     */
    public function definitions(): array
    {
        return config('tich-leave.types', []);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function definition(string $code): ?array
    {
        $code = strtoupper(trim($code));

        return $this->definitions()[$code] ?? null;
    }

    public function definitionForType(LeaveType $type): array
    {
        return $this->definition((string) $type->leave_code) ?? [
            'leave_name' => $type->leave_name,
            'days_allowed_per_year' => (int) $type->days_allowed_per_year,
            'calculation' => $type->calculation_type ?: 'working_days',
            'requires_document' => (bool) ($type->requires_certificate || $type->requires_medical_certificate),
            'gender_restriction' => $type->gender_restriction ?: 'any',
            'available' => (bool) $type->is_active,
            'carry_forward_days' => (int) $type->carry_forward_days,
            'accrual' => $type->accrual_type ?: 'none',
            'accrual_rate' => $type->accrual_rate,
        ];
    }

    /**
     * @return Collection<int, LeaveType>
     */
    public function availableTypes(): Collection
    {
        $this->ensureSynced();

        $codes = collect($this->definitions())
            ->filter(fn (array $def) => ($def['available'] ?? true) === true)
            ->keys()
            ->all();

        return LeaveType::query()
            ->whereIn('leave_code', $codes)
            ->orderBy('leave_name')
            ->get();
    }

    public function typeByCode(string $code): ?LeaveType
    {
        $this->ensureSynced();

        return LeaveType::query()->where('leave_code', strtoupper(trim($code)))->first();
    }

    public function ensureSynced(): void
    {
        if (! Schema::hasTable('leave_types')) {
            return;
        }

        foreach ($this->definitions() as $code => $def) {
            DB::table('leave_types')->updateOrInsert(
                ['leave_code' => $code],
                [
                    'leave_name' => $def['leave_name'],
                    'days_allowed_per_year' => (int) ($def['days_allowed_per_year'] ?? 0),
                    'accrual_type' => ($def['accrual'] ?? 'none') === 'monthly' ? 'monthly' : 'none',
                    'accrual_rate' => $def['accrual_rate'] ?? null,
                    'calculation_type' => $def['calculation'] ?? 'working_days',
                    'is_paid' => 1,
                    'requires_medical_certificate' => ! empty($def['requires_document']) && $code === 'SICK' ? 1 : 0,
                    'requires_certificate' => ! empty($def['requires_document']) ? 1 : 0,
                    'requires_hod_approval' => 0,
                    'requires_hr_approval' => 1,
                    'gender_restriction' => $def['gender_restriction'] ?? 'any',
                    'min_service_months' => 0,
                    'carry_forward_days' => (int) ($def['carry_forward_days'] ?? 0),
                    'max_consecutive_days' => null,
                    'notice_period_days' => 0,
                    'description' => $def['description'] ?? null,
                    'is_active' => ($def['available'] ?? true) ? 1 : 0,
                ]
            );
        }
    }

    public function usesWorkingDays(string $code): bool
    {
        $def = $this->definition($code);

        return ($def['calculation'] ?? 'working_days') === 'working_days';
    }

    public function requiresDocument(string $code): bool
    {
        return (bool) ($this->definition($code)['requires_document'] ?? false);
    }

    public function requiresFamilyRelation(string $code): bool
    {
        return (bool) ($this->definition($code)['requires_family_relation'] ?? false);
    }

    /**
     * @return list<string>
     */
    public function familyRelations(): array
    {
        return array_values(config('tich-leave.family_relations', ['mother', 'father', 'child', 'spouse']));
    }
}
