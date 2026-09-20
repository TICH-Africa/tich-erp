<?php

namespace App\Services;

use App\Models\Student;
use App\Models\StudentFinancialRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class StudentFinancialService
{
    public function addPastRecord(Student $student, array $data): StudentFinancialRecord
    {
        return DB::transaction(function () use ($student, $data) {
            $record = StudentFinancialRecord::create([
                'student_id' => $student->id,
                'academic_year_id' => $data['academic_year_id'] ?? null,
                'semester_id' => $data['semester_id'] ?? null,
                'total_chargeable' => $data['total_chargeable'] ?? 0,
                'total_paid' => $data['total_paid'] ?? 0,
                'outstanding_balance' => $data['outstanding_balance'] ?? 0,
                'payment_method' => $data['payment_method'] ?? null,
                'payment_reference' => $data['payment_reference'] ?? null,
                'payment_date' => $data['payment_date'] ?? null,
                'recorded_by' => Auth::id(),
                'notes' => $data['notes'] ?? null,
            ]);

            return $record;
        });
    }

    public function getForStudent(Student $student): \Illuminate\Database\Eloquent\Collection
    {
        return StudentFinancialRecord::forStudent($student->id)->get();
    }

    public function getSummary(Student $student): array
    {
        $records = $this->getForStudent($student);
        $totalChargeable = $records->sum('total_chargeable');
        $totalPaid = $records->sum('total_paid');

        return [
            'total_records' => $records->count(),
            'total_chargeable' => $totalChargeable,
            'total_paid' => $totalPaid,
            'total_outstanding' => $totalChargeable - $totalPaid,
        ];
    }
}
