<?php

namespace Tests\Unit\Academics;

use App\Models\AcademicProgram;
use App\Models\CurriculumVersion;
use App\Models\Semester;
use App\Models\Student;
use App\Models\User;
use App\Services\AuditService;
use App\Services\CurriculumVersionService;
use App\Services\ExamScheduleSyncService;
use App\Services\Sidebar\PortalSidebarBroadcastService;
use App\Services\StudentUnitRegistrationService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class StudentUnitRegistrationServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
                'foreign_key_constraints' => true,
            ],
        ]);
        DB::purge('sqlite');

        Schema::create('semesters', function (Blueprint $table): void {
            $table->id();
            $table->string('semester_label');
            $table->unsignedInteger('semester_number');
        });
        Schema::create('student_semester_registrations', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('semester_id');
            $table->unsignedInteger('unit_count')->default(0);
        });
        Schema::create('registered_units', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('semester_registration_id');
            $table->unsignedBigInteger('unit_id');
            $table->boolean('is_additional')->default(false);
            $table->timestamp('created_at')->nullable();
        });
    }

    public function test_year_of_study_uses_the_student_recorded_semester_when_admission_date_is_missing(): void
    {
        DB::table('semesters')->insert([
            'id' => 3,
            'semester_label' => 'Semester 3',
            'semester_number' => 3,
        ]);

        $student = new Student;
        $student->forceFill([
            'current_year' => null,
            'current_semester_id' => 3,
            'date_of_admission' => null,
            'cohort_intake' => null,
        ]);

        $year = $this->service()->yearOfStudy(
            $student,
            new AcademicProgram([
                'curriculum_format' => 'semester',
                'semester_count' => 2,
                'duration_months' => 48,
                'is_tiered' => false,
                'is_nursing_track' => false,
            ]),
        );

        $this->assertSame(2, $year['year']);
        $this->assertSame(3, $year['period']);
    }

    public function test_roster_year_uses_the_latest_registered_semester_when_student_has_no_year_or_admission_date(): void
    {
        DB::table('semesters')->insert([
            'id' => 3,
            'semester_label' => 'Semester 3',
            'semester_number' => 3,
        ]);
        DB::table('student_semester_registrations')->insert([
            'id' => 71,
            'student_id' => 17,
            'semester_id' => 3,
        ]);
        DB::table('registered_units')->insert([
            'semester_registration_id' => 71,
            'unit_id' => 42,
        ]);

        $student = new Student;
        $student->forceFill([
            'id' => 17,
            'current_year' => null,
            'current_semester_id' => null,
            'date_of_admission' => null,
            'cohort_intake' => null,
        ]);

        $years = $this->service()->yearsForRoster(
            collect([$student]),
            new AcademicProgram([
                'curriculum_format' => 'semester',
                'semester_count' => 2,
                'duration_months' => 48,
                'is_tiered' => false,
                'is_nursing_track' => false,
            ]),
        );

        $this->assertSame(2, $years->get(17)['year']);
    }

    public function test_selecting_a_teaching_period_overrides_a_stale_year_filter_for_the_registration_roster(): void
    {
        Schema::create('students', function (Blueprint $table): void {
            $table->id();
            $table->string('registration_number');
            $table->unsignedBigInteger('program_id');
            $table->unsignedBigInteger('application_id')->nullable();
            $table->unsignedBigInteger('enrollment_campus_id')->nullable();
            $table->unsignedTinyInteger('current_year')->nullable();
            $table->unsignedBigInteger('current_semester_id')->nullable();
            $table->unsignedTinyInteger('hold_cleared_period')->nullable();
            $table->string('enrollment_status')->nullable();
            $table->string('entry_pathway')->nullable();
            $table->string('study_mode')->nullable();
            $table->date('date_of_admission')->nullable();
            $table->string('cohort_intake')->nullable();
        });
        Schema::create('applicants', function (Blueprint $table): void {
            $table->id();
            $table->unsignedInteger('intake_year')->nullable();
            $table->unsignedTinyInteger('intake_month')->nullable();
        });
        Schema::create('campuses', function (Blueprint $table): void {
            $table->id();
        });
        Schema::create('curriculum_version_periods', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('curriculum_version_id');
            $table->unsignedInteger('semester');
            $table->unsignedBigInteger('block_id')->nullable();
            $table->date('start_date')->nullable();
        });
        Schema::create('units', function (Blueprint $table): void {
            $table->id();
            $table->string('unit_code');
        });
        Schema::create('grade_records', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('unit_id');
            $table->string('grade_letter')->nullable();
        });

        DB::table('students')->insert([
            ['id' => 1, 'registration_number' => 'STU-001', 'program_id' => 7, 'current_year' => 1],
            ['id' => 2, 'registration_number' => 'STU-002', 'program_id' => 7, 'current_year' => 2],
        ]);

        $versions = \Mockery::mock(CurriculumVersionService::class);
        $versions->shouldReceive('mappedUnits')->twice()->andReturn(collect());
        $schedule = \Mockery::mock(ExamScheduleSyncService::class);
        $schedule->shouldReceive('resolveSemesterId')->twice()->andReturn(104);
        $service = new StudentUnitRegistrationService(
            $versions,
            $schedule,
            \Mockery::mock(AuditService::class),
            \Mockery::mock(PortalSidebarBroadcastService::class),
        );
        $program = new AcademicProgram([
            'curriculum_format' => 'semester',
            'semester_count' => 2,
            'duration_months' => 48,
            'is_tiered' => false,
            'is_nursing_track' => false,
        ]);
        $program->id = 7;
        $intake = new CurriculumVersion([
            'program_id' => 7,
            'intake_year' => 2026,
            'intake_month' => 1,
        ]);
        $intake->id = 19;

        $staleYearRequest = Request::create('/?registration_period=sem-4&study_year=1');
        $periodSelectionRequest = Request::create('/?registration_period=sem-4&study_year=1&registration_period_selection=1');

        $this->assertSame(
            [1],
            $service->desk($staleYearRequest, $program, $intake)['students']->pluck('id')->all(),
        );
        $selectedPeriod = $service->desk($periodSelectionRequest, $program, $intake);

        $this->assertSame([2], $selectedPeriod['students']->pluck('id')->all());
        $this->assertSame(2, $selectedPeriod['studyYearFilter']);
    }

    public function test_removing_unit_clears_registrations_and_dependent_exam_requests(): void
    {
        Schema::create('students', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
        });
        Schema::create('units', function (Blueprint $table): void {
            $table->id();
            $table->string('unit_code');
            $table->string('unit_name');
        });
        Schema::create('grade_records', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('unit_id');
            $table->string('grade_letter')->nullable();
        });
        Schema::create('exam_results', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('unit_id');
        });

        foreach (['supplementary_requests', 'special_exam_requests', 'remark_requests'] as $tableName) {
            Schema::create($tableName, function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('exam_result_id');
                $table->foreign('exam_result_id')->references('id')->on('exam_results')->restrictOnDelete();
            });
        }

        DB::table('semesters')->insert([
            'id' => 3,
            'semester_label' => 'Semester 3',
            'semester_number' => 3,
        ]);
        DB::table('students')->insert(['id' => 17, 'user_id' => 29]);
        DB::table('units')->insert(['id' => 42, 'unit_code' => 'UNIT42', 'unit_name' => 'Test unit']);
        DB::table('student_semester_registrations')->insert([
            'id' => 71,
            'student_id' => 17,
            'semester_id' => 3,
            'unit_count' => 1,
        ]);
        DB::table('registered_units')->insert([
            'id' => 91,
            'semester_registration_id' => 71,
            'unit_id' => 42,
        ]);
        DB::table('exam_results')->insert(['id' => 101, 'student_id' => 17, 'unit_id' => 42]);

        foreach (['supplementary_requests', 'special_exam_requests', 'remark_requests'] as $tableName) {
            DB::table($tableName)->insert(['exam_result_id' => 101]);
        }

        $this->service(expectBroadcast: true)->removeUnit(
            new User,
            Student::findOrFail(17),
            42,
            'Wrong unit assignment',
        );

        $this->assertDatabaseMissing('registered_units', ['unit_id' => 42]);
        $this->assertDatabaseMissing('exam_results', ['id' => 101]);

        foreach (['supplementary_requests', 'special_exam_requests', 'remark_requests'] as $tableName) {
            $this->assertDatabaseCount($tableName, 0);
        }
    }

    private function service(bool $expectBroadcast = false): StudentUnitRegistrationService
    {
        $sidebar = \Mockery::mock(PortalSidebarBroadcastService::class);
        $sidebar->shouldReceive('broadcastStudentAcademics')
            ->times($expectBroadcast ? 1 : 0);

        $audits = \Mockery::mock(AuditService::class);
        $audits->shouldReceive('log')->times($expectBroadcast ? 1 : 0);

        return new StudentUnitRegistrationService(
            \Mockery::mock(CurriculumVersionService::class),
            \Mockery::mock(ExamScheduleSyncService::class),
            $audits,
            $sidebar,
        );
    }
}
