<?php

namespace Tests\Unit\Academics;

use App\Models\AcademicProgram;
use App\Models\Student;
use App\Models\User;
use App\Services\SupplementaryExamService;
use App\Services\TranscriptDocumentService;
use App\Services\TranscriptService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * The supplementary sitting end to end: the fail, the repeat, and the one transcript row the
 * repeat leaves behind.
 *
 * These run on the empty test schema inside a transaction that is rolled back, so a fresh
 * migrate:fresh is not required to prove the marks path.
 */
class SupplementaryExamMarksTest extends TestCase
{
    use DatabaseTransactions;

    private AcademicProgram $program;

    private Student $student;

    private User $officer;

    private int $unitId;

    private int $semesterId;

    private int $gradeRecordId;

    protected function setUp(): void
    {
        parent::setUp();

        $departmentId = DB::table('departments')->insertGetId([
            'dept_code' => 'TEST-ACA',
            'dept_name' => 'Test Academics',
            'dept_category' => 'academic',
            'is_active' => 1,
        ]);

        $campusId = DB::table('campuses')->insertGetId([
            'campus_name' => 'Test Campus',
            'campus_type' => 'main',
            'is_active' => 1,
        ]);

        $yearId = DB::table('academic_years')->insertGetId([
            'year_label' => '2026/2027',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
        ]);

        $this->semesterId = (int) DB::table('semesters')->insertGetId([
            'academic_year_id' => $yearId,
            'semester_label' => 'Semester 1',
            'semester_number' => 1,
            'start_date' => '2026-01-01',
            'end_date' => '2026-04-30',
        ]);

        $this->program = AcademicProgram::create([
            'program_code' => 'TEST-DEG',
            'program_name' => 'Test Diploma',
            'program_type' => 'diploma',
            'department_id' => $departmentId,
            'theory_pass_mark' => 40,
        ]);

        DB::table('curriculum_versions')->insert([
            'program_id' => $this->program->id,
            'version_label' => 'Jan 2026 intake',
            'curriculum_format' => 'semester',
            'status' => 'published',
        ]);

        $this->unitId = (int) DB::table('units')->insertGetId([
            'unit_code' => 'TEST-101',
            'unit_name' => 'Test Unit',
            'program_id' => $this->program->id,
            'department_id' => $departmentId,
            'credit_hours' => 3,
            'status' => 'active',
            'assessment_weight_cat_pct' => 30,
            'assessment_weight_practical_pct' => 15,
            'assessment_weight_attendance_pct' => 5,
            'assessment_weight_exam_pct' => 50,
        ]);

        $applicantId = DB::table('applicants')->insertGetId([
            'application_number' => 'APP-TEST-1',
            'program_id' => $this->program->id,
            'first_name' => 'Asha',
            'surname' => 'Mwangi',
            'date_of_birth' => '2004-01-01',
            'gender' => 'Female',
            'email' => 'asha@test.africa',
            'phone_number' => '0700000001',
            'status' => 'admitted',
        ]);

        $this->student = Student::create([
            'registration_number' => 'REG-TEST-1',
            'first_name' => '',
            'surname' => '',
            'application_id' => $applicantId,
            'program_id' => $this->program->id,
            'enrollment_campus_id' => $campusId,
            'cohort_intake' => '2026/01',
            'date_of_admission' => '2026-01-05',
            'enrollment_status' => 'active',
        ]);

        $staffId = DB::table('staff')->insertGetId([
            'employee_number' => 'EMP/TEST/0001',
            'first_name' => 'Officer',
            'surname' => 'Test',
            'date_of_birth' => '1990-01-01',
            'gender' => 'Female',
            'primary_email' => 'officer@test.africa',
            'phone_number' => '0700000002',
            'department_id' => $departmentId,
            'job_title' => 'Academics Officer',
            'employment_category' => 'permanent',
            'employment_start_date' => '2024-01-01',
            'employment_status' => 'active',
        ]);

        $this->officer = User::factory()->create(['user_type' => 'admin']);

        DB::table('staff')->where('id', $staffId)->update(['user_id' => $this->officer->id]);

        $cardId = DB::table('exam_cards')->insertGetId([
            'exam_card_number' => 'CARD-TEST-1',
            'student_id' => $this->student->id,
            'semester_id' => $this->semesterId,
            'issued_at' => '2026-04-01',
        ]);

        // One CAT at 60% with the default 30/15/5/50 profile banks a continuous score of 36.
        DB::table('cat_scores')->insert([
            'student_id' => $this->student->id,
            'unit_id' => $this->unitId,
            'semester_id' => $this->semesterId,
            'assessment_type' => 'cat',
            'assessment_name' => 'CAT 1',
            'max_score' => 50,
            'score_obtained' => 30,
            'percentage_score' => 60,
            'weight_in_final' => 30,
            'recorded_by' => $staffId,
            'recorded_at' => '2026-03-01 09:00:00',
        ]);

        // The sitting that failed: 36 continuous plus a 20 exam out of 50 weights is 28 - an F.
        DB::table('exam_results')->insert([
            'exam_card_id' => $cardId,
            'student_id' => $this->student->id,
            'unit_id' => $this->unitId,
            'semester_id' => $this->semesterId,
            'cat_total' => 30,
            'final_exam_score' => 20,
            'final_total_score' => 28,
            'grade_letter' => 'F',
            'grade_points' => 0,
            'theory_pass_check' => 0,
            'is_supplementary' => 0,
            'entered_by' => $staffId,
            'created_at' => '2026-04-20 09:00:00',
            'updated_at' => '2026-04-20 09:00:00',
        ]);

        $this->gradeRecordId = (int) DB::table('grade_records')->insertGetId([
            'student_id' => $this->student->id,
            'unit_id' => $this->unitId,
            'semester_id' => $this->semesterId,
            'final_score' => 28,
            'grade_letter' => 'F',
            'grade_points' => 0,
            'credit_hours' => 3,
            'recorded_at' => '2026-04-20 09:00:00',
            'created_at' => '2026-04-20 09:00:00',
        ]);
    }

    public function test_outstanding_lists_the_fail_with_the_mark_that_would_pass_it(): void
    {
        $rows = app(SupplementaryExamService::class)
            ->outstanding([$this->unitId], [$this->student->id]);

        $this->assertCount(1, $rows);

        $owed = $rows->first();
        $this->assertSame('REG-TEST-1', $owed->registration_number);
        $this->assertSame('Asha Mwangi', $owed->student_name);
        $this->assertSame(28.0, $owed->failed_total);
        $this->assertSame(20.0, $owed->failed_exam_score);
        $this->assertSame(36.0, $owed->continuous_cumulative);
        // 36 continuous carries 18 of the 40 pass mark, so half of the remaining 22 is 44.
        $this->assertSame(44.0, $owed->pass_exam_score);
        $this->assertNull($owed->supplementary);
    }

    public function test_a_supplementary_mark_replaces_the_fail_on_the_transcript(): void
    {
        app(SupplementaryExamService::class)
            ->record($this->officer, $this->program, [$this->gradeRecordId => 75]);

        $sittings = DB::table('exam_results')
            ->where(['student_id' => $this->student->id, 'unit_id' => $this->unitId, 'semester_id' => $this->semesterId])
            ->orderBy('is_supplementary')
            ->get(['id', 'final_exam_score', 'final_total_score', 'grade_letter', 'is_supplementary', 'supplementary_triggered']);

        // The fail stays on record and the repeat is the row that replaces it.
        $this->assertCount(2, $sittings);
        $this->assertSame(20.0, (float) $sittings[0]->final_exam_score);
        $this->assertSame(1, (int) $sittings[0]->supplementary_triggered);
        $this->assertSame(75.0, (float) $sittings[1]->final_exam_score);
        $this->assertSame('C', $sittings[1]->grade_letter);

        $grade = DB::table('grade_records')->where('id', $this->gradeRecordId)->first();
        $this->assertSame(55.5, (float) $grade->final_score);
        $this->assertSame('C', $grade->grade_letter);
        $this->assertSame(2.0, (float) $grade->grade_points);
        $this->assertSame(1, (int) $grade->is_supplementary_pass);
        $this->assertSame((int) $sittings[1]->id, (int) $grade->exam_result_id);

        $transcript = app(TranscriptService::class)->build($this->student);
        $row = collect($transcript['rows'])->firstWhere('unit_code', 'TEST-101');

        // One row, not two: the fail's credits may not count alongside the repeat's.
        $this->assertCount(1, $transcript['rows']);
        $this->assertSame(55.5, $row->final_score);
        $this->assertSame('C', $row->grade_letter);
        $this->assertSame(75.0, $row->exam_score);
        $this->assertTrue($row->is_supplementary);
        $this->assertSame(3.0, $transcript['total_credits']);
        $this->assertSame(2.0, $transcript['cumulative_gpa']);

        $audit = DB::table('audit_logs')->where('action', 'academics.exams.supplementary_recorded')->first();
        $this->assertNotNull($audit);
        $this->assertSame('F', json_decode((string) $audit->old_value, true)['grade_letter']);

        $printed = view('sis.transcript.print', app(TranscriptDocumentService::class)->documentData($transcript))->render();
        $this->assertStringContainsString('<sup>Supp</sup>', $printed);
        $this->assertStringContainsString('Supp marks a unit carried by a supplementary examination', $printed);
        $this->assertMatchesRegularExpression(
            '/Continuous<\/th>\s*<th[^>]*>Exam<\/th>\s*<th[^>]*>Final<\/th>\s*<th[^>]*>Grade<\/th>\s*<th[^>]*>Points<\/th>/',
            $printed
        );
    }

    public function test_re_recording_updates_the_one_supplementary_sitting(): void
    {
        $service = app(SupplementaryExamService::class);
        $service->record($this->officer, $this->program, [$this->gradeRecordId => 75]);
        $service->record($this->officer, $this->program, [$this->gradeRecordId => 45]);

        $sittings = DB::table('exam_results')
            ->where(['student_id' => $this->student->id, 'unit_id' => $this->unitId, 'semester_id' => $this->semesterId])
            ->where('is_supplementary', 1)
            ->get(['final_exam_score', 'final_total_score', 'grade_letter']);

        $this->assertCount(1, $sittings);
        $this->assertSame(45.0, (float) $sittings[0]->final_exam_score);
        $this->assertSame(40.5, (float) $sittings[0]->final_total_score);
        $this->assertSame('D', $sittings[0]->grade_letter);

        $transcript = app(TranscriptService::class)->build($this->student);
        $this->assertSame(40.5, collect($transcript['rows'])->first()->final_score);
    }

    public function test_recording_for_another_programmes_marks_is_ignored(): void
    {
        $this->expectException(\Illuminate\Validation\ValidationException::class);

        app(SupplementaryExamService::class)->record($this->officer, $this->program, [999999 => 75]);
    }
}
