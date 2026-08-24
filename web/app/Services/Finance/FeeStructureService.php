<?php

namespace App\Services\Finance;

use App\Models\FeeStructure;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class FeeStructureService
{
    public function paginated(?int $programId = null): LengthAwarePaginator
    {
        return FeeStructure::query()
            ->with(['program.department', 'academicYear', 'approver'])
            ->when($programId, fn ($query) => $query->where('program_id', $programId))
            ->orderByDesc('effective_from')
            ->paginate(25)
            ->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): FeeStructure
    {
        $programId = $data['program_id'] ?? null;
        $academicYearId = $data['academic_year_id'] ?? null;

        if ($programId && $academicYearId) {
            $recent = FeeStructure::query()
                ->where('program_id', $programId)
                ->where('academic_year_id', $academicYearId)
                ->where('created_at', '>=', now()->subSeconds(90))
                ->orderByDesc('id')
                ->first();

            if ($recent) {
                return $recent->fresh(['program', 'academicYear']);
            }
        }

        $feeStructure = new FeeStructure($data);
        $feeStructure->recalculateTotal();
        $feeStructure->save();

        return $feeStructure->fresh(['program', 'academicYear']);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(FeeStructure $feeStructure, array $data): FeeStructure
    {
        $feeStructure->fill($data);
        $feeStructure->recalculateTotal();
        $feeStructure->save();

        return $feeStructure->fresh(['program', 'academicYear']);
    }

    public function approve(FeeStructure $feeStructure, int $staffId): FeeStructure
    {
        $feeStructure->update([
            'is_approved' => 1,
            'approved_by' => $staffId,
            'approved_at' => now(),
        ]);

        return $feeStructure->fresh(['program', 'academicYear', 'approver']);
    }
}
