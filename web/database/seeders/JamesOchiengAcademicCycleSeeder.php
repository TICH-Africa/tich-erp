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

class JamesOchiengAcademicCycleSeeder extends Seeder
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
        $staff = $this->resolveJamesOchieng();

        if (! $staff) {
            $this->command?->warn('James Ochieng lecturer profile not found — run LecturerSeeder first.');

            return;
        }

        $programId = (int) (DB::table('academic_programs')->where('program_code', 'HMD-CC')->value('id')
            ?? DB::table('academic_programs')->value('id'));

        if (! $programId) {
            $this->command?->warn('HMD-CC programme not found — run ProgramsSeeder first.');

            return;
        }

        $this->call(AcademicCalendarDemoSeeder::class);

        $semesterId = (int) (DB::table('semesters')->where('semester_number', 1)->value('id')
            ?? DB::table('semesters')->orderByDesc('id')->value('id'));

        $campusId = (int) (DB::table('campuses')->where('is_active', 1)->value('id') ?? 1);

        $unit = Unit::query()->where('unit_code', 'HMDCC-01')->first()
            ?? Unit::query()->where('program_id', $programId)->orderBy('unit_code')->first();

        if (! $unit || ! $semesterId) {
            $this->command?->warn('Teaching unit or semester missing — run HmdCcUnitsSeeder first.');

            return;
        }

        $allocation = $this->ensureAllocation($staff, $unit, $semesterId, $campusId);
        $assessments = app(ContinuousAssessmentService::class);
        $passMark = (float) (DB::table('academic_programs')->where('id', $programId)->value('theory_pass_mark') ?? 40);

        $this->seedExamSchedule((int) $unit->id, $semesterId);
        $this->seedLessonPlan($allocation, $staff);

        $studentIds = [];

        foreach ($this->studentProfiles() as $profile) {
            $studentId = $this->ensureStudent($profile, $programId, $campusId, $semesterId);
            if (! $studentId) {
                continue;
            }

            $studentIds[$profile['reg']] = $studentId;
            $this->ensureRegistration($studentId, $semesterId, (int) $unit->id);
            $this->seedAttendanceSessions($allocation, $staff, $studentId, (float) $profile['attendance']);
            $this->seedAttendanceSummary($studentId, (int) $unit->id, $semesterId, (float) $profile['attendance'], $profile['fees_cleared'] ?? true);
            $this->seedExamEligibility($studentId, (int) $unit->id, $semesterId, (float) $profile['attendance'], $profile['fees_cleared'] ?? true);

            foreach ($this->assessments as $assessment) {
                $raw = $profile['scores'][$assessment['name']] ?? null;
                if ($raw === null) {
                    continue;
                }

                $this->seedCatScore($staff, $studentId, (int) $unit->id, $semesterId, $assessment, (float) $raw);
            }

            $continuous = $assessments->continuousBreakdown($studentId, $allocation);
            $examScore = $profile['exam'] ?? null;

            if ($examScore !== null) {
                $this->seedExamAndGrade(
                    $staff,
                    $studentId,
                    $unit,
                    $semesterId,
                    $continuous,
                    (float) $examScore,
                    $assessments,
                    $passMark,
                );
            } elseif (($profile['cycle'] ?? '') !== 'in_progress') {
                DB::table('grade_records')->updateOrInsert(
                    ['student_id' => $studentId, 'unit_id' => $unit->id, 'semester_id' => $semesterId],
                    [
                        'final_score' => $continuous['cumulative'],
                        'grade_letter' => $assessments->gradeLetterForScore($continuous['cumulative'], $passMark),
                        'grade_points' => $this->gradePoints($assessments->gradeLetterForScore($continuous['cumulative'], $passMark)),
                        'credit_hours' => $unit->credit_hours ?? 3,
                        'recorded_at' => now(),
                        'created_at' => now(),
                    ]
                );
            }

            if (($profile['semester_complete'] ?? false) === true) {
                $this->seedCompletedSemesterUnits($staff, $studentId, $programId, $semesterId, $profile);
            }

            if (($profile['advance_semester'] ?? false) === true) {
                $nextSemesterId = DB::table('semesters')->where('semester_number', 2)->value('id');
                if ($nextSemesterId) {
                    DB::table('students')->where('id', $studentId)->update([
                        'current_semester_id' => $nextSemesterId,
                        'enrollment_status' => 'active',
                        'updated_at' => now(),
                    ]);
                }
            }
        }

        $this->linkStudentPortalUser($studentIds['REG-2026-HMD01'] ?? reset($studentIds) ?: null);

        DB::table('program_timetable_sessions')
            ->where('unit_id', $unit->id)
            ->whereNull('staff_id')
            ->update(['staff_id' => $staff->id]);

        $this->command?->info(sprintf(
            'James Ochieng academic cycle seeded: unit %s, %d students, allocation #%d.',
            $unit->unit_code,
            count($studentIds),
            $allocation->id,
        ));
    }

    public static function resolveJamesOchieng(): ?Staff
    {
        return Staff::query()
            ->where('employee_number', 'EMP-LECT-001')
            ->orWhere('organisation_email', 'lecturer@tich.ac.ke')
            ->orWhere('primary_email', 'james.ochieng@tich.africa')
            ->orWhereHas('user', fn ($query) => $query->where('email', 'lecturer@tich.ac.ke'))
            ->first();
    }

    private function ensureAllocation(Staff $staff, Unit $unit, int $semesterId, int $campusId): UnitAllocation
    {
        $existing = UnitAllocation::query()
            ->where('staff_id', $staff->id)
            ->where('unit_id', $unit->id)
            ->where('semester_id', $semesterId)
            ->orderByDesc('id')
            ->first();

        if ($existing) {
            $existing->update([
                'campus_id' => $campusId,
                'contact_hours_assigned' => 4,
                'is_coordinator' => 1,
                'is_active' => 1,
            ]);

            return $existing->fresh(['unit', 'semester']);
        }

        UnitAllocation::query()
            ->where('staff_id', $staff->id)
            ->update(['is_active' => 0]);

        return UnitAllocation::query()->create([
            'unit_id' => $unit->id,
            'staff_id' => $staff->id,
            'semester_id' => $semesterId,
            'campus_id' => $campusId,
            'contact_hours_assigned' => 4,
            'is_coordinator' => 1,
            'is_active' => 1,
        ])->fresh(['unit', 'semester']);
    }

    /**
     * @param  array<string, mixed>  $profile
     */
    private function seedCompletedSemesterUnits(Staff $staff, int $studentId, int $programId, int $semesterId, array $profile): void
    {
        $unitCodes = ['HMDCC-02', 'HMDCC-03'];
        $grades = $profile['semester_unit_grades'] ?? [62.0, 68.5];

        foreach ($unitCodes as $index => $code) {
            $unitId = (int) Unit::query()->where('unit_code', $code)->where('program_id', $programId)->value('id');
            if (! $unitId) {
                continue;
            }

            $this->ensureRegistration($studentId, $semesterId, $unitId);
            $this->seedAttendanceSummary($studentId, $unitId, $semesterId, 93.0, true);
            $this->seedExamEligibility($studentId, $unitId, $semesterId, 93.0, true);

            $examScore = (float) ($grades[$index] ?? 60.0);
            $gradeLetter = $examScore >= 70 ? 'A' : ($examScore >= 60 ? 'B' : ($examScore >= 50 ? 'C' : ($examScore >= 40 ? 'D' : 'F')));

            DB::table('cat_scores')->updateOrInsert(
                ['student_id' => $studentId, 'unit_id' => $unitId, 'semester_id' => $semesterId, 'assessment_name' => 'CAT 1'],
                [
                    'assessment_type' => 'cat',
                    'max_score' => 30,
                    'score_obtained' => min(28, $examScore * 0.4),
                    'percentage_score' => min(93.0, $examScore * 1.2),
                    'weight_in_final' => 0,
                    'recorded_by' => $staff->id,
                    'recorded_at' => now(),
                    'updated_at' => now(),
                ]
            );

            $examCardId = $this->ensureExamCard($studentId, $semesterId);

            DB::table('exam_results')->updateOrInsert(
                ['student_id' => $studentId, 'unit_id' => $unitId, 'semester_id' => $semesterId],
                [
                    'exam_card_id' => $examCardId,
                    'cat_total' => 24.0,
                    'practical_total' => 22.0,
                    'final_exam_score' => $examScore,
                    'final_total_score' => $examScore,
                    'grade_letter' => $gradeLetter,
                    'grade_points' => $this->gradePoints($gradeLetter),
                    'entered_by' => $staff->id,
                    'updated_at' => now(),
                ]
            );

            DB::table('grade_records')->updateOrInsert(
                ['student_id' => $studentId, 'unit_id' => $unitId, 'semester_id' => $semesterId],
                [
                    'final_score' => $examScore,
                    'grade_letter' => $gradeLetter,
                    'grade_points' => $this->gradePoints($gradeLetter),
                    'credit_hours' => 3,
                    'recorded_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
    }

    private function seedLessonPlan(UnitAllocation $allocation, Staff $staff): void
    {
        DB::table('lesson_plans')->updateOrInsert(
            ['plan_number' => 'LP-JO-HMDCC01-W1'],
            [
                'unit_allocation_id' => $allocation->id,
                'prepared_by' => $staff->id,
                'lesson_title' => 'Introduction to community health practice',
                'lesson_objectives' => 'Define community health; explain primary health care pillars; identify local health determinants.',
                'topics_covered' => 'Community diagnosis, PHC, health promotion basics',
                'competencies_targeted' => 'CHP-01, CHP-02',
                'contact_hours' => 4,
                'week_number' => 1,
                'planned_date' => '2026-02-10',
                'teaching_methods' => 'Lecture, group discussion, case study',
                'resources_required' => 'Projector, flip charts, community profile handout',
                'status' => 'approved',
                'hod_id' => $staff->id,
                'hod_action_at' => now()->subDays(3),
                'registrar_visible' => 1,
                'source_type' => 'form',
                'tutor_verified_at' => now()->subDays(5),
                'updated_at' => now(),
            ]
        );
    }

    private function seedExamSchedule(int $unitId, int $semesterId): void
    {
        DB::table('exam_schedules')->updateOrInsert(
            ['unit_id' => $unitId, 'semester_id' => $semesterId, 'exam_type' => 'main'],
            [
                'exam_date' => '2026-04-14',
                'start_time' => '08:00:00',
                'end_time' => '10:00:00',
                'venue' => 'Hall A',
                'total_candidates' => 5,
                'status' => 'completed',
            ]
        );
    }

    private function seedAttendanceSessions(UnitAllocation $allocation, Staff $staff, int $studentId, float $attendancePct): void
    {
        $totalSessions = 10;
        $targetPresent = (int) round(($attendancePct / 100) * $totalSessions);

        for ($i = 1; $i <= $totalSessions; $i++) {
            $sessionNumber = sprintf('ATT-JO-%s-%02d', $allocation->id, $i);
            $sessionDate = now()->subWeeks($totalSessions - $i)->toDateString();
            $isPresent = $i <= $targetPresent;

            $sessionId = DB::table('attendance_sessions')->where('session_number', $sessionNumber)->value('id');

            if (! $sessionId) {
                $sessionId = DB::table('attendance_sessions')->insertGetId([
                    'session_number' => $sessionNumber,
                    'unit_allocation_id' => $allocation->id,
                    'session_date' => $sessionDate,
                    'start_time' => '08:00:00',
                    'end_time' => '10:00:00',
                    'venue' => 'Room 101',
                    'session_type' => 'physical',
                    'total_expected_attendees' => 5,
                    'recorded_by' => $staff->id,
                    'recorded_at' => now()->subWeeks($totalSessions - $i),
                    'is_locked' => $i <= 8 ? 1 : 0,
                    'verification_status' => $i <= 6 ? 'registrar_verified' : ($i <= 8 ? 'submitted' : 'draft'),
                    'submitted_at' => $i <= 8 ? now()->subWeeks($totalSessions - $i)->addDay() : null,
                    'hod_verified_by' => $i <= 8 ? $staff->id : null,
                    'hod_verified_at' => $i <= 8 ? now()->subWeeks($totalSessions - $i)->addDays(2) : null,
                    'registrar_verified_by' => $i <= 6 ? $staff->id : null,
                    'registrar_verified_at' => $i <= 6 ? now()->subWeeks($totalSessions - $i)->addDays(3) : null,
                ]);
            }

            DB::table('attendance_records')->updateOrInsert(
                ['session_id' => $sessionId, 'student_id' => $studentId],
                [
                    'is_present' => $isPresent ? 1 : 0,
                    'sign_in_time' => $isPresent ? '08:05:00' : null,
                    'recorded_by_tutor' => 1,
                    'verified_by_hod' => $i <= 8 ? 1 : 0,
                ]
            );
        }
    }

    /**
     * @param  array{name: string, type: string, max: float}  $assessment
     */
    private function seedCatScore(
        Staff $staff,
        int $studentId,
        int $unitId,
        int $semesterId,
        array $assessment,
        float $raw,
    ): void {
        $score = min($raw, $assessment['max']);
        $percentage = $assessment['max'] > 0 ? round(($score / $assessment['max']) * 100, 2) : 0;

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
                'recorded_at' => now()->subDays(7),
                'updated_at' => now(),
            ]
        );
    }

    /**
     * @param  array<string, mixed>  $continuous
     */
    private function seedExamAndGrade(
        Staff $staff,
        int $studentId,
        Unit $unit,
        int $semesterId,
        array $continuous,
        float $examScore,
        ContinuousAssessmentService $assessments,
        float $passMark,
    ): void {
        $finalTotal = $assessments->finalScoreWithExam($continuous['cumulative'], $examScore, $unit);
        $gradeLetter = $assessments->gradeLetterForScore($finalTotal, $passMark);
        $examCardId = $this->ensureExamCard($studentId, $semesterId);

        DB::table('exam_results')->updateOrInsert(
            ['student_id' => $studentId, 'unit_id' => $unit->id, 'semester_id' => $semesterId],
            [
                'exam_card_id' => $examCardId,
                'cat_total' => $continuous['cat_avg'],
                'practical_total' => $continuous['practical_avg'],
                'final_exam_score' => $examScore,
                'final_total_score' => $finalTotal,
                'grade_letter' => $gradeLetter,
                'grade_points' => $this->gradePoints($gradeLetter),
                'entered_by' => $staff->id,
                'updated_at' => now(),
            ]
        );

        DB::table('grade_records')->updateOrInsert(
            ['student_id' => $studentId, 'unit_id' => $unit->id, 'semester_id' => $semesterId],
            [
                'final_score' => $finalTotal,
                'grade_letter' => $gradeLetter,
                'grade_points' => $this->gradePoints($gradeLetter),
                'credit_hours' => $unit->credit_hours ?? 3,
                'recorded_at' => now(),
                'created_at' => now(),
            ]
        );
    }

    private function seedAttendanceSummary(int $studentId, int $unitId, int $semesterId, float $attendance, bool $feeCleared): void
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
                'exam_eligibility_blocked' => ($attendance >= 90 && $feeCleared) ? 0 : 1,
            ]
        );
    }

    private function seedExamEligibility(int $studentId, int $unitId, int $semesterId, float $attendance, bool $feeCleared): void
    {
        $eligible = $attendance >= 90 && $feeCleared;

        DB::table('exam_eligibility_matrix')->updateOrInsert(
            ['student_id' => $studentId, 'unit_id' => $unitId, 'semester_id' => $semesterId],
            [
                'attendance_percentage' => $attendance,
                'attendance_check_passed' => $attendance >= 90 ? 1 : 0,
                'fee_clearance_check_passed' => $feeCleared ? 1 : 0,
                'eligible_for_exams' => $eligible ? 1 : 0,
                'calculated_at' => now(),
                'created_at' => now(),
            ]
        );
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
                'cycle' => 'completed',
                'semester_complete' => true,
                'semester_unit_grades' => [62.0, 68.5],
                'advance_semester' => true,
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
                'cycle' => 'completed',
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
                'cycle' => 'completed',
                'semester_complete' => true,
                'semester_unit_grades' => [74.0, 78.5],
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
                'cycle' => 'in_progress',
                'fees_cleared' => false,
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
                'cycle' => 'in_progress',
                'scores' => [
                    'CAT 1' => 22, 'CAT 2' => 23, 'Theoretical review 1' => 15,
                    'Assignment 1' => 16, 'Practical 1' => 20, 'Skills lab 1' => 21,
                ],
            ],
            [
                'reg' => 'REG-2026-HMD06',
                'first' => 'Frank',
                'surname' => 'Otieno',
                'email' => 'frank.otieno.demo@tich.ac.ke',
                'attendance' => 90.5,
                'exam' => 35.0,
                'cycle' => 'completed',
                'scores' => [
                    'CAT 1' => 15, 'CAT 2' => 16, 'Theoretical review 1' => 10,
                    'Assignment 1' => 11, 'Practical 1' => 14, 'Skills lab 1' => 15,
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
            'fee_clearance_status' => ($profile['fees_cleared'] ?? true) ? 'cleared' : 'partial',
            'overall_balance' => ($profile['fees_cleared'] ?? true) ? 0 : 15000,
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
                'unit_count' => 3,
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
            'exam_card_number' => 'EC-JO-'.strtoupper(Str::random(4)),
            'student_id' => $studentId,
            'semester_id' => $semesterId,
            'issued_at' => now()->subDays(14),
        ]);
    }

    private function linkStudentPortalUser(?int $studentId): void
    {
        if (! $studentId) {
            return;
        }

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
