<?php

namespace App\Services;

use App\Models\AcademicProgram;
use App\Models\AcademicRecord;
use App\Models\Student;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class AcademicRecordService
{
    public function create(Student $student, array $data): AcademicRecord
    {
        return DB::transaction(function () use ($student, $data) {
            $record = AcademicRecord::create([
                'student_id' => $student->id,
                'program_id' => $data['program_id'] ?? $student->program_id,
                'academic_year_id' => $data['academic_year_id'] ?? null,
                'semester_id' => $data['semester_id'] ?? null,
                'enrollment_date' => $data['enrollment_date'] ?? now()->toDateString(),
                'completion_date' => $data['completion_date'] ?? null,
                'status' => $data['status'] ?? 'active',
                'gpa' => $data['gpa'] ?? null,
                'units_registered' => $data['units_registered'] ?? 0,
                'units_completed' => $data['units_completed'] ?? 0,
                'entry_pathway' => $data['entry_pathway'] ?? null,
                'created_by' => Auth::id(),
                'notes' => $data['notes'] ?? null,
            ]);

            $student->program_id = $record->program_id;
            $student->save();

            return $record;
        });
    }

    public function getHistory(Student $student): \Illuminate\Database\Eloquent\Collection
    {
        return AcademicRecord::forStudent($student->id)->get();
    }

    public function getForProgram(AcademicProgram $program): \Illuminate\Database\Eloquent\Collection
    {
        return AcademicRecord::forProgram($program->id)->with('student')->get();
    }
}