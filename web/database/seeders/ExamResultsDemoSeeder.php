<?php

namespace Database\Seeders;

use App\Support\IntakeIdentity;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ExamResultsDemoSeeder extends Seeder
{
    public function run(): void
    {
        $programId = DB::table('academic_programs')->where('program_code', 'HMD-CC')->value('id');

        if (! $programId) {
            $this->command?->warn('HMD-CC program not found — skipping exam results demo seed.');

            return;
        }

        $semesterId = DB::table('semesters')->where('semester_number', 1)->value('id');
        $campusId = DB::table('campuses')->where('is_active', 1)->value('id') ?? 1;
        $unitIds = [1, 2, 3];

        if (! $semesterId || DB::table('units')->whereIn('id', $unitIds)->count() < 3) {
            $this->command?->warn('Required semester or units missing — skipping exam results demo seed.');

            return;
        }

        $this->seedExamSchedules($unitIds, (int) $semesterId);

        $demoStudents = [
            ['reg' => 'REG-2026-HMD01', 'first' => 'Alice', 'surname' => 'Kamau', 'email' => 'alice.kamau.demo@tich.ac.ke', 'fees' => 'cleared', 'attendance' => [94.0, 91.5, 96.0], 'eligible' => [1, 1, 1], 'grades' => [58.5, 62.0, 55.0]],
            ['reg' => 'REG-2026-HMD02', 'first' => 'Brian', 'surname' => 'Ochieng', 'email' => 'brian.ochieng.demo@tich.ac.ke', 'fees' => 'cleared', 'attendance' => [88.0, 90.0, 92.5], 'eligible' => [1, 1, 1], 'grades' => [48.0, 51.5, 49.0]],
            ['reg' => 'REG-2026-HMD03', 'first' => 'Carol', 'surname' => 'Wanjiku', 'email' => 'carol.wanjiku.demo@tich.ac.ke', 'fees' => 'cleared', 'attendance' => [95.0, 93.0, 97.0], 'eligible' => [1, 1, 1], 'grades' => [72.0, 68.5, 70.0]],
            ['reg' => 'REG-2026-HMD04', 'first' => 'David', 'surname' => 'Mutua', 'email' => 'david.mutua.demo@tich.ac.ke', 'fees' => 'partial', 'attendance' => [92.0, 90.0, 68.0], 'eligible' => [1, 1, 0], 'grades' => [44.0, 46.5, 38.0], 'flags' => ['green', 'green', 'red']],
        ];

        foreach ($demoStudents as $index => $profile) {
            $studentId = $this->ensureStudent($profile, (int) $programId, (int) $campusId, (int) $semesterId);

            if (! $studentId) {
                continue;
            }

            $this->seedSemesterRegistration($studentId, (int) $semesterId, $profile['fees'] === 'cleared');
            $this->seedStudentUnits($studentId, (int) $semesterId, $unitIds, $profile);
        }

        $this->command?->info('Exam results demo data seeded for HMD-CC Jan 2026 intake, Semester 1.');
    }

    /**
     * @param  list<int>  $unitIds
     */
    private function seedExamSchedules(array $unitIds, int $semesterId): void
    {
        $dates = ['2026-04-14', '2026-04-15', '2026-04-16'];
        $venues = ['Hall A', 'Hall B', 'Lab 1'];

        foreach ($unitIds as $i => $unitId) {
            $exists = DB::table('exam_schedules')
                ->where('unit_id', $unitId)
                ->where('semester_id', $semesterId)
                ->exists();

            if ($exists) {
                continue;
            }

            DB::table('exam_schedules')->insert([
                'unit_id' => $unitId,
                'semester_id' => $semesterId,
                'exam_date' => $dates[$i] ?? '2026-04-14',
                'start_time' => '08:00:00',
                'end_time' => '10:00:00',
                'venue' => $venues[$i] ?? 'Hall A',
                'exam_type' => 'main',
                'total_candidates' => 0,
                'status' => 'scheduled',
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $profile
     */
    private function ensureStudent(array $profile, int $programId, int $campusId, int $semesterId): ?int
    {
        $existing = DB::table('students')->where('registration_number', $profile['reg'])->value('id');

        if ($existing) {
            return (int) $existing;
        }

        $applicantId = DB::table('applicants')->where('email', $profile['email'])->value('id');

        if (! $applicantId) {
            $applicantId = DB::table('applicants')->insertGetId([
                'application_number' => 'APP-DEMO-'.strtoupper(Str::random(6)),
                'program_id' => $programId,
                'intake_year' => 2026,
                'intake_month' => 1,
                'preferred_campus_id' => $campusId,
                'first_name' => $profile['first'],
                'surname' => $profile['surname'],
                'date_of_birth' => '2000-01-15',
                'gender' => 'female',
                'email' => $profile['email'],
                'phone_number' => '+2547000000'.rand(10, 99),
                'status' => 'admitted',
                'academic_review_status' => 'approved',
                'created_at' => now(),
            ]);
        }

        return (int) DB::table('students')->insertGetId([
            'registration_number' => $profile['reg'],
            'application_id' => $applicantId,
            'program_id' => $programId,
            'cohort_intake' => IntakeIdentity::cohortLabel(2026, 1),
            'enrollment_campus_id' => $campusId,
            'current_semester_id' => $semesterId,
            'enrollment_status' => 'active',
            'entry_pathway' => 'regular',
            'date_of_admission' => '2026-01-15',
            'fee_clearance_status' => $profile['fees'],
            'overall_balance' => $profile['fees'] === 'cleared' ? 0 : 15000,
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function seedSemesterRegistration(int $studentId, int $semesterId, bool $feeCleared): void
    {
        DB::table('student_semester_registrations')->updateOrInsert(
            ['student_id' => $studentId, 'semester_id' => $semesterId],
            [
                'registration_date' => '2026-01-20',
                'registration_type' => 'admin',
                'unit_count' => 3,
                'status' => 'registered',
                'is_fee_cleared' => $feeCleared ? 1 : 0,
            ]
        );
    }

    /**
     * @param  list<int>  $unitIds
     * @param  array<string, mixed>  $profile
     */
    private function seedStudentUnits(int $studentId, int $semesterId, array $unitIds, array $profile): void
    {
        $flags = $profile['flags'] ?? ['green', 'green', 'green'];

        foreach ($unitIds as $i => $unitId) {
            $attendance = (float) ($profile['attendance'][$i] ?? 90);
            $flag = $flags[$i] ?? ($attendance >= 90 ? 'green' : ($attendance >= 75 ? 'amber' : 'red'));
            $eligible = (int) ($profile['eligible'][$i] ?? 1);
            $feeCleared = $profile['fees'] === 'cleared' ? 1 : 0;
            $grade = (float) ($profile['grades'][$i] ?? 50);
            $gradeLetter = $grade >= 70 ? 'A' : ($grade >= 60 ? 'B' : ($grade >= 50 ? 'C' : ($grade >= 40 ? 'D' : 'F')));

            DB::table('attendance_summaries')->updateOrInsert(
                ['student_id' => $studentId, 'unit_id' => $unitId, 'semester_id' => $semesterId],
                [
                    'total_sessions' => 10,
                    'total_present' => (int) round($attendance / 10),
                    'attendance_percentage' => $attendance,
                    'status_flag' => $flag,
                    'last_calculated_at' => now(),
                    'exam_eligibility_blocked' => $eligible ? 0 : 1,
                ]
            );

            DB::table('exam_eligibility_matrix')->updateOrInsert(
                ['student_id' => $studentId, 'unit_id' => $unitId, 'semester_id' => $semesterId],
                [
                    'attendance_percentage' => $attendance,
                    'attendance_check_passed' => $attendance >= 90 ? 1 : 0,
                    'fee_clearance_check_passed' => $feeCleared,
                    'eligible_for_exams' => ($eligible && $feeCleared && $attendance >= 90) ? 1 : 0,
                    'calculated_at' => now(),
                    'created_at' => now(),
                ]
            );

            DB::table('grade_records')->updateOrInsert(
                ['student_id' => $studentId, 'unit_id' => $unitId, 'semester_id' => $semesterId],
                [
                    'final_score' => $grade,
                    'grade_letter' => $gradeLetter,
                    'grade_points' => match ($gradeLetter) {
                        'A' => 4.0,
                        'B' => 3.0,
                        'C' => 2.0,
                        'D' => 1.0,
                        default => 0.0,
                    },
                    'credit_hours' => 3,
                    'recorded_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
    }
}
