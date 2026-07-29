<?php

namespace Database\Seeders;

use App\Models\Staff;
use App\Models\Unit;
use App\Models\UnitAllocation;
use App\Models\User;
use App\Services\ContinuousAssessmentService;
use App\Support\IntakeIdentity;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class StaffMarksDemoSeeder extends Seeder
{
    /**
     * @var list<array{name: string, type: string, max: float}>
     */
    private array $assessments = [
        ['name' => 'CAT 1', 'type' => 'cat', 'max' => 30],
        ['name' => 'CAT 2', 'type' => 'cat', 'max' => 30],
        ['name' => 'Theoretical review 1', 'type' => 'theoretical_review', 'max' => 20],
        ['name' => 'Assignment 1', 'type' => 'assignment', 'max' => 20],
        ['name' => 'Practical 1', 'type' => 'practical', 'max' => 25],
        ['name' => 'Skills lab 1', 'type' => 'skills_lab', 'max' => 25],
    ];

    public function run(): void
    {
        $staff = Staff::query()->where('email', 'lecturer@tich.ac.ke')->first();

        if (! $staff) {
            $this->command?->warn('Demo lecturer not found — run LecturerSeeder first.');

            return;
        }

        $allocation = UnitAllocation::query()
            ->where('staff_id', $staff->id)
            ->where('is_active', 1)
            ->with(['unit', 'semester'])
            ->orderByDesc('id')
            ->first();

        if (! $allocation) {
            $this->command?->warn('No unit allocation for demo lecturer — skipping marks demo seed.');

            return;
        }

        $unit = $allocation->unit;
        $semesterId = (int) $allocation->semester_id;
        $unitId = (int) $allocation->unit_id;
        $programId = (int) ($unit?->program_id
            ?? DB::table('academic_programs')->where('program_code', 'HMD-CC')->value('id')
            ?? DB::table('academic_programs')->value('id'));

        if (! $programId) {
            $this->command?->warn('No program found for marks demo seed.');

            return;
        }

        $campusId = (int) ($allocation->campus_id
            ?? DB::table('campuses')->where('is_active', 1)->value('id')
            ?? 1);

        $studentProfiles = $this->studentProfiles();
        $studentIds = [];

        foreach ($studentProfiles as $profile) {
            $studentId = $this->ensureStudent($profile, $programId, $campusId, $semesterId);
            if ($studentId) {
                $this->ensureRegistration($studentId, $semesterId, $unitId);
                $studentIds[$profile['reg']] = $studentId;
            }
        }

        if ($studentIds === []) {
            $this->command?->warn('No demo students created for marks seed.');

            return;
        }

        $assessments = app(ContinuousAssessmentService::class);
        $passMark = (float) (DB::table('academic_programs')->where('id', $programId)->value('theory_pass_mark') ?? 40);

        foreach ($studentProfiles as $profile) {
            $studentId = $studentIds[$profile['reg']] ?? null;
            if (! $studentId) {
                continue;
            }

            $this->seedAttendance($studentId, $unitId, $semesterId, (float) $profile['attendance']);

            foreach ($this->assessments as $assessment) {
                $raw = $profile['scores'][$assessment['name']] ?? null;
                if ($raw === null) {
                    continue;
                }

                $score = min((float) $raw, $assessment['max']);
                $percentage = $assessment['max'] > 0
                    ? round(($score / $assessment['max']) * 100, 2)
                    : 0;

                DB::table('cat_scores')->updateOrInsert(
                    [
                        'student_id' => $studentId,
                        'unit_id' => $unitId,
                        'semester_id' => $semesterId,
                        'assessment_name' => $assessment['name'],
                    ],
                    [
                        'assessment_type' => $assessment['type'],
                        'max_score' => $assessment['max'],
                        'score_obtained' => $score,
                        'percentage_score' => $percentage,
                        'weight_in_final' => 0,
                        'recorded_by' => $staff->id,
                        'recorded_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }

            $continuous = $assessments->continuousBreakdown($studentId, $allocation);
            $examScore = $profile['exam'] ?? null;

            if ($examScore !== null) {
                $finalTotal = $assessments->finalScoreWithExam(
                    $continuous['cumulative'],
                    (float) $examScore,
                    $unit,
                );
                $gradeLetter = $assessments->gradeLetterForScore($finalTotal, $passMark);
                $examCardId = $this->ensureExamCard($studentId, $semesterId);

                DB::table('exam_results')->updateOrInsert(
                    [
                        'student_id' => $studentId,
                        'unit_id' => $unitId,
                        'semester_id' => $semesterId,
                    ],
                    [
                        'exam_card_id' => $examCardId,
                        'cat_total' => $continuous['cat_avg'],
                        'practical_total' => $continuous['practical_avg'],
                        'final_exam_score' => (float) $examScore,
                        'final_total_score' => $finalTotal,
                        'grade_letter' => $gradeLetter,
                        'grade_points' => $this->gradePoints($gradeLetter),
                        'entered_by' => $staff->id,
                        'updated_at' => now(),
                    ]
                );

                DB::table('grade_records')->updateOrInsert(
                    [
                        'student_id' => $studentId,
                        'unit_id' => $unitId,
                        'semester_id' => $semesterId,
                    ],
                    [
                        'final_score' => $finalTotal,
                        'grade_letter' => $gradeLetter,
                        'grade_points' => $this->gradePoints($gradeLetter),
                        'credit_hours' => $unit?->credit_hours ?? 3,
                        'recorded_at' => now(),
                        'created_at' => now(),
                    ]
                );
            } else {
                DB::table('grade_records')->updateOrInsert(
                    [
                        'student_id' => $studentId,
                        'unit_id' => $unitId,
                        'semester_id' => $semesterId,
                    ],
                    [
                        'final_score' => $continuous['cumulative'],
                        'grade_letter' => $assessments->gradeLetterForScore($continuous['cumulative'], $passMark),
                        'grade_points' => $this->gradePoints($assessments->gradeLetterForScore($continuous['cumulative'], $passMark)),
                        'credit_hours' => $unit?->credit_hours ?? 3,
                        'recorded_at' => now(),
                        'created_at' => now(),
                    ]
                );
            }
        }

        $this->linkStudentPortalUser($studentIds['REG-2026-HMD01'] ?? reset($studentIds));

        $this->command?->info(sprintf(
            'Seeded CAT and exam marks for %d students on %s (%s).',
            count($studentIds),
            $unit?->unit_code ?? 'unit '.$unitId,
            $allocation->semester?->semester_label ?? 'semester '.$semesterId,
        ));
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function studentProfiles(): array
    {
        return [
            [
                'reg' => 'REG-2026-HMD01',
                'first' => 'Alice',
                'surname' => 'Kamau',
                'email' => 'alice.kamau.demo@tich.ac.ke',
                'attendance' => 94.0,
                'exam' => 58.0,
                'scores' => [
                    'CAT 1' => 24, 'CAT 2' => 26, 'Theoretical review 1' => 16,
                    'Assignment 1' => 17, 'Practical 1' => 21, 'Skills lab 1' => 22,
                ],
            ],
            [
                'reg' => 'REG-2026-HMD02',
                'first' => 'Brian',
                'surname' => 'Ochieng',
                'email' => 'brian.ochieng.demo@tich.ac.ke',
                'attendance' => 91.5,
                'exam' => 48.0,
                'scores' => [
                    'CAT 1' => 20, 'CAT 2' => 22, 'Theoretical review 1' => 14,
                    'Assignment 1' => 15, 'Practical 1' => 18, 'Skills lab 1' => 19,
                ],
            ],
            [
                'reg' => 'REG-2026-HMD03',
                'first' => 'Carol',
                'surname' => 'Wanjiku',
                'email' => 'carol.wanjiku.demo@tich.ac.ke',
                'attendance' => 96.0,
                'exam' => 72.0,
                'scores' => [
                    'CAT 1' => 28, 'CAT 2' => 27, 'Theoretical review 1' => 18,
                    'Assignment 1' => 19, 'Practical 1' => 23, 'Skills lab 1' => 24,
                ],
            ],
            [
                'reg' => 'REG-2026-HMD04',
                'first' => 'David',
                'surname' => 'Mutua',
                'email' => 'david.mutua.demo@tich.ac.ke',
                'attendance' => 88.0,
                'exam' => null,
                'scores' => [
                    'CAT 1' => 18, 'CAT 2' => 19, 'Theoretical review 1' => 12,
                    'Assignment 1' => 13, 'Practical 1' => 16, 'Skills lab 1' => 17,
                ],
            ],
            [
                'reg' => 'REG-2026-HMD05',
                'first' => 'Esther',
                'surname' => 'Njeri',
                'email' => 'esther.njeri.demo@tich.ac.ke',
                'attendance' => 92.5,
                'exam' => null,
                'scores' => [
                    'CAT 1' => 22, 'CAT 2' => 23, 'Theoretical review 1' => 15,
                    'Assignment 1' => 16, 'Practical 1' => 20, 'Skills lab 1' => 21,
                ],
            ],
        ];
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
                'date_of_birth' => '2001-06-15',
                'gender' => 'female',
                'email' => $profile['email'],
                'phone_number' => '+254700'.rand(100000, 999999),
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
            'fee_clearance_status' => 'cleared',
            'overall_balance' => 0,
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function ensureRegistration(int $studentId, int $semesterId, int $unitId): void
    {
        DB::table('student_semester_registrations')->updateOrInsert(
            ['student_id' => $studentId, 'semester_id' => $semesterId],
            [
                'registration_date' => '2026-01-20',
                'registration_type' => 'admin',
                'unit_count' => 1,
                'status' => 'registered',
                'is_fee_cleared' => 1,
            ]
        );

        $registrationId = DB::table('student_semester_registrations')
            ->where('student_id', $studentId)
            ->where('semester_id', $semesterId)
            ->value('id');

        if ($registrationId) {
            DB::table('registered_units')->updateOrInsert(
                ['semester_registration_id' => $registrationId, 'unit_id' => $unitId],
                ['is_additional' => 0, 'created_at' => now()],
            );
        }
    }

    private function seedAttendance(int $studentId, int $unitId, int $semesterId, float $attendance): void
    {
        $flag = $attendance >= 90 ? 'green' : ($attendance >= 75 ? 'amber' : 'red');

        DB::table('attendance_summaries')->updateOrInsert(
            ['student_id' => $studentId, 'unit_id' => $unitId, 'semester_id' => $semesterId],
            [
                'total_sessions' => 10,
                'total_present' => (int) round($attendance / 10),
                'attendance_percentage' => $attendance,
                'status_flag' => $flag,
                'last_calculated_at' => now(),
                'exam_eligibility_blocked' => $attendance >= 90 ? 0 : 1,
            ]
        );
    }

    private function ensureExamCard(int $studentId, int $semesterId): int
    {
        $existing = DB::table('exam_cards')
            ->where('student_id', $studentId)
            ->where('semester_id', $semesterId)
            ->value('id');

        if ($existing) {
            return (int) $existing;
        }

        return (int) DB::table('exam_cards')->insertGetId([
            'exam_card_number' => 'EC-DEMO-'.strtoupper(Str::random(4)),
            'student_id' => $studentId,
            'semester_id' => $semesterId,
            'issued_at' => now(),
        ]);
    }

    private function linkStudentPortalUser(int $studentId): void
    {
        $user = User::query()->where('email', 'student@tich.ac.ke')->first();
        if (! $user) {
            return;
        }

        $user->update(['student_id' => $studentId]);
        DB::table('students')->where('id', $studentId)->update(['user_id' => $user->id]);
    }

    private function gradePoints(string $letter): float
    {
        return match ($letter) {
            'A' => 4.0,
            'B' => 3.0,
            'C' => 2.0,
            'D' => 1.0,
            default => 0.0,
        };
    }
}
