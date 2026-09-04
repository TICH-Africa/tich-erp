<?php

namespace App\Services;

use App\Models\Student;
use App\Models\StudentClearanceItem;
use Illuminate\Support\Collection;

class StudentClearanceService
{
    /**
     * Ensure default clearance checklist rows exist and sync known statuses.
     *
     * @return Collection<int, StudentClearanceItem>
     */
    public function ensureDefaults(Student $student): Collection
    {
        $defaults = [
            'finance' => 'Finance',
            'library' => 'Library',
            'hostels' => 'Hostels',
            'academics' => 'Academic department',
            'registrar' => 'Registrar',
        ];

        foreach ($defaults as $key => $label) {
            StudentClearanceItem::query()->firstOrCreate(
                [
                    'student_id' => $student->id,
                    'department_key' => $key,
                ],
                [
                    'label' => $label,
                    'status' => 'pending',
                ]
            );
        }

        $items = StudentClearanceItem::query()
            ->where('student_id', $student->id)
            ->orderBy('id')
            ->get();

        $feeStatus = strtolower((string) ($student->fee_clearance_status ?? 'pending'));
        $academicStatus = strtolower((string) ($student->academic_clearance_status ?? 'pending'));

        foreach ($items as $item) {
            if ($item->department_key === 'finance') {
                $mapped = in_array($feeStatus, ['cleared', 'clear'], true) ? 'cleared' : ($feeStatus === 'blocked' ? 'blocked' : 'pending');
                if ($item->status !== $mapped) {
                    $item->update(['status' => $mapped]);
                }
            }

            if ($item->department_key === 'academics') {
                $mapped = in_array($academicStatus, ['cleared', 'clear'], true) ? 'cleared' : 'pending';
                if ($item->status !== $mapped) {
                    $item->update([
                        'status' => $mapped,
                        'cleared_at' => $mapped === 'cleared' ? ($student->academically_cleared_at ?? $item->cleared_at) : null,
                        'cleared_by_user_id' => $mapped === 'cleared' ? ($student->academically_cleared_by ?? $item->cleared_by_user_id) : null,
                    ]);
                }
            }
        }

        return StudentClearanceItem::query()
            ->where('student_id', $student->id)
            ->orderBy('id')
            ->get();
    }
}
