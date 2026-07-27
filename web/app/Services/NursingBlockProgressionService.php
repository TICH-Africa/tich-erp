<?php

namespace App\Services;

use App\Models\AcademicProgram;
use App\Models\NursingBlock;
use App\Models\Student;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class NursingBlockProgressionService
{
    public function getBlockProgressStatus(int $studentId): array
    {
        $student = Student::query()->find($studentId);

        if (! $student?->is_nursing_student) {
            return [];
        }

        $blocks = NursingBlock::query()
            ->where('program_id', $student->program_id)
            ->orderBy('block_order')
            ->get();

        $status = [];

        foreach ($blocks as $block) {
            $clinicalPassed = DB::table('clinical_logs')
                ->where('student_id', $student->id)
                ->where('block_id', $block->id)
                ->where('status', 'passed')
                ->count();

            $skillsPassed = DB::table('skills_assessments')
                ->where('student_id', $student->id)
                ->where('block_id', $block->id)
                ->where('status', 'passed')
                ->count();

            $unitsCompleted = DB::table('student_unit_enrollments')
                ->join('curriculum_version_units', 'student_unit_enrollments.unit_id', '=', 'curriculum_version_units.unit_id')
                ->where('curriculum_version_units.block_id', $block->id)
                ->where('student_unit_enrollments.student_id', $student->id)
                ->where('student_unit_enrollments.status', 'completed')
                ->count();

            $status[$block->id] = [
                'block_label' => $block->block_label,
                'block_order' => $block->block_order,
                'clinical_passed' => $clinicalPassed,
                'skills_passed' => $skillsPassed,
                'units_completed' => $unitsCompleted,
                'can_progress' => $clinicalPassed > 0 && $skillsPassed > 0 && $unitsCompleted > 0,
            ];
        }

        return $status;
    }

    public function canProceedToBlock(Student $student, int $blockId): bool
    {
        $targetBlock = NursingBlock::query()->find($blockId);
        if (! $targetBlock) {
            return false;
        }

        $previousBlocks = NursingBlock::query()
            ->where('program_id', $targetBlock->program_id)
            ->where('block_order', '<', $targetBlock->block_order)
            ->orderBy('block_order')
            ->get();

        foreach ($previousBlocks as $block) {
            if (! $this->hasPassedBlock($student->id, $block->id)) {
                return false;
            }
        }

        return true;
    }

    private function hasPassedBlock(int $studentId, int $blockId): bool
    {
        $clinicalLogs = DB::table('clinical_logs')
            ->where('student_id', $studentId)
            ->where('block_id', $blockId)
            ->where('status', 'passed')
            ->exists();

        $skillsAssessments = DB::table('skills_assessments')
            ->where('student_id', $studentId)
            ->where('block_id', $blockId)
            ->where('status', 'passed')
            ->exists();

        return $clinicalLogs && $skillsAssessments;
    }

    public function validateEnrollment(int $studentId, int $blockId): void
    {
        $student = Student::query()->find($studentId);

        if (! $student?->is_nursing_student) {
            return;
        }

        if (! $this->canProceedToBlock($student, $blockId)) {
            throw ValidationException::withMessages([
                'block_id' => 'Student must complete and pass clinical logs and skills assessments for the previous block before enrolling.',
            ]);
        }
    }

    public function getAllBlocksWithProgress(?int $programId = null): Collection
    {
        $query = NursingBlock::query()->with('programUnits');

        if ($programId) {
            $query->where('program_id', $programId);
        }

        return $query->orderBy('block_order')->get()->map(function ($block) {
            $students = DB::table('students')
                ->where('program_id', $block->program_id)
                ->where('is_nursing_student', 1)
                ->select('id', 'registration_number', 'first_name', 'surname')
                ->get()
                ->map(function ($student) use ($block) {
                    $clinicalPassed = DB::table('clinical_logs')
                        ->where('student_id', $student->id)
                        ->where('block_id', $block->id)
                        ->where('status', 'passed')
                        ->count();

                    $skillsPassed = DB::table('skills_assessments')
                        ->where('student_id', $student->id)
                        ->where('block_id', $block->id)
                        ->where('status', 'passed')
                        ->count();

                    $unitsCompleted = DB::table('student_unit_enrollments')
                        ->join('curriculum_version_units', 'student_unit_enrollments.unit_id', '=', 'curriculum_version_units.unit_id')
                        ->where('curriculum_version_units.block_id', $block->id)
                        ->where('student_unit_enrollments.student_id', $student->id)
                        ->where('student_unit_enrollments.status', 'completed')
                        ->count();

                    $student->clinical_passed = $clinicalPassed;
                    $student->skills_passed = $skillsPassed;
                    $student->units_completed = $unitsCompleted;
                    $student->can_progress = $clinicalPassed > 0 && $skillsPassed > 0 && $unitsCompleted > 0;

                    return $student;
                });

            $block->students = $students;

            return $block;
        });
    }
}