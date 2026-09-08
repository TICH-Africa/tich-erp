<?php

namespace App\Services\Sidebar;

use App\Models\CourseEvaluation;
use App\Models\CourseEvaluationWindow;
use App\Models\ProgramTimetable;
use App\Models\SpecialExamRequest;
use App\Models\Student;
use App\Models\StudentClearanceItem;
use App\Models\StudentDocumentRequest;
use App\Models\StudentLifecycleRequest;
use App\Models\StudentNotification;
use App\Models\StudentProfileChangeRequest;
use App\Models\StudentTranscriptRequest;
use App\Models\SupplementaryExamRequest;
use App\Models\User;
use App\Services\Sidebar\Concerns\FormatsSidebarBadgeCounts;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class StudentSidebarNotificationService
{
    use FormatsSidebarBadgeCounts;

    public const CACHE_KEY_PREFIX = 'student.sidebar.counts.';

    public const CACHE_TTL_SECONDS = 30;

    /** @var array<string, string> */
    public const MENU_KEYS = [
        'overview' => 'Overview',
        'notifications' => 'Notifications',
        'profile' => 'My profile',
        'enrolment' => 'Enrolment',
        'documents' => 'Documents',
        'requests' => 'Deferment',
        'clearance' => 'Clearance',
        'evaluations' => 'Course evaluations',
        'finance' => 'Finance',
        'account' => 'Security & account',
        'academics' => 'Academics',
        'academics.units' => 'My Units',
        'academics.content' => 'Learning Content',
        'academics.assessments' => 'Assessments',
        'academics.exams' => 'Exams & Grades',
        'academics.exam-requests' => 'Supplementary & Special Exams',
        'academics.progress' => 'Academic progress',
        'academics.eligibility' => 'Exam eligibility',
        'academics.calendar' => 'Academic calendar',
        'academics.registration' => 'Unit registration',
        'academics.attendance' => 'Attendance',
        'timetable' => 'Timetable',
        'timetable.lesson' => 'Lesson Timetable',
        'timetable.exam' => 'Exam Timetable',
        'suggestions' => 'Suggestion box',
    ];

    /**
     * @return array<string, int>
     */
    public function countsFor(Student $student, bool $fresh = false): array
    {
        $cacheKey = self::CACHE_KEY_PREFIX.$student->id;

        if ($fresh) {
            $counts = $this->computeCounts($student);
            Cache::put($cacheKey, $counts, self::CACHE_TTL_SECONDS);

            return $counts;
        }

        return Cache::remember($cacheKey, self::CACHE_TTL_SECONDS, fn () => $this->computeCounts($student));
    }

    /**
     * @return array<string, string|null>
     */
    public function formattedCountsFor(Student $student, bool $fresh = false): array
    {
        return $this->formattedCounts($this->countsFor($student, $fresh));
    }

    public function forget(Student $student): void
    {
        Cache::forget(self::CACHE_KEY_PREFIX.$student->id);
    }

    public function badgeKeyForSection(string $section): ?string
    {
        return match ($section) {
            'overview',
            'notifications',
            'profile',
            'enrolment',
            'documents',
            'requests',
            'clearance',
            'evaluations',
            'finance',
            'account',
            'academics',
            'timetable',
            'suggestions' => $section,
            default => null,
        };
    }

    public function badgeKeyForAcademicsTab(string $tab): ?string
    {
        return match ($tab) {
            'units' => 'academics.units',
            'content' => 'academics.content',
            'assessments' => 'academics.assessments',
            'exams' => 'academics.exams',
            'exam-requests' => 'academics.exam-requests',
            'progress' => 'academics.progress',
            'eligibility' => 'academics.eligibility',
            'calendar' => 'academics.calendar',
            'registration' => 'academics.registration',
            'attendance' => 'academics.attendance',
            default => null,
        };
    }

    public function badgeKeyForTimetableTab(string $tab): ?string
    {
        return match ($tab) {
            'lesson' => 'timetable.lesson',
            'exam' => 'timetable.exam',
            default => null,
        };
    }

    /**
     * @return array<string, int>
     */
    private function computeCounts(Student $student): array
    {
        $notifications = 0;
        if (Schema::hasTable('student_notifications')) {
            $notifications = StudentNotification::query()
                ->where('student_id', $student->id)
                ->whereNull('read_at')
                ->count();
        }

        $profile = 0;
        if (Schema::hasTable('student_profile_change_requests')) {
            $profile = StudentProfileChangeRequest::query()
                ->where('student_id', $student->id)
                ->where('status', 'pending')
                ->count();
        }

        $documents = 0;
        if (Schema::hasTable('student_document_requests')) {
            $documents = StudentDocumentRequest::query()
                ->where('student_id', $student->id)
                ->where(function ($q) {
                    $q->whereIn('status', ['pending', 'processing'])
                        ->orWhere(function ($issued) {
                            $issued->where('status', 'issued')
                                ->where('issued_at', '>=', now()->subDays(14));
                        });
                })
                ->count();
        }

        $requests = 0;
        if (Schema::hasTable('student_lifecycle_requests')) {
            $requests = StudentLifecycleRequest::query()
                ->where('student_id', $student->id)
                ->whereIn('status', ['pending', 'processing'])
                ->count();
        }

        $clearance = 0;
        if (Schema::hasTable('student_clearance_items')) {
            $clearance = StudentClearanceItem::query()
                ->where('student_id', $student->id)
                ->whereIn('status', ['pending', 'blocked'])
                ->count();
        }

        $evaluations = 0;
        if (Schema::hasTable('course_evaluation_windows') && Schema::hasTable('course_evaluations')) {
            $openWindowIds = CourseEvaluationWindow::query()
                ->where('is_active', true)
                ->where('opens_at', '<=', now())
                ->where('closes_at', '>=', now())
                ->pluck('id');

            if ($openWindowIds->isNotEmpty()) {
                $submitted = CourseEvaluation::query()
                    ->where('student_id', $student->id)
                    ->whereIn('window_id', $openWindowIds)
                    ->whereNotNull('submitted_at')
                    ->pluck('window_id')
                    ->unique();

                $evaluations = $openWindowIds->diff($submitted)->count();
            }
        }

        $outstanding = (float) ($student->overall_balance ?? 0);
        $needsClearance = ($student->fee_clearance_status ?? 'pending') !== 'cleared';
        $finance = ($outstanding > 0 || $needsClearance) ? 1 : 0;

        $enrolment = 0;
        if (! in_array($student->enrollment_status, ['active', 'enrolled', 'registered', 'graduated'], true)) {
            $enrolment = 1;
        } elseif ($needsClearance && in_array($student->enrollment_status, ['pending', 'admitted', 'offered'], true)) {
            $enrolment = 1;
        }

        $account = 0;
        $user = $student->user_id
            ? User::query()->find($student->user_id)
            : User::query()->where('student_id', $student->id)->first();
        if ($user && ! $user->mfa_enabled) {
            $account = 1;
        }

        $exams = 0;
        if (Schema::hasTable('student_transcript_requests')) {
            $exams += StudentTranscriptRequest::query()
                ->where('student_id', $student->id)
                ->whereIn('status', ['pending', 'processing'])
                ->count();

            $exams += StudentTranscriptRequest::query()
                ->where('student_id', $student->id)
                ->where('status', 'issued')
                ->where('issued_at', '>=', now()->subDays(14))
                ->count();
        }

        if (Schema::hasTable('exam_eligibility_matrix') && Schema::hasTable('exam_schedules')) {
            $blockedUpcoming = (int) DB::table('exam_eligibility_matrix as eem')
                ->join('exam_schedules as es', function ($join) {
                    $join->on('es.unit_id', '=', 'eem.unit_id')
                        ->on('es.semester_id', '=', 'eem.semester_id');
                })
                ->where('eem.student_id', $student->id)
                ->where('eem.eligible_for_exams', false)
                ->whereDate('es.exam_date', '>=', now()->toDateString())
                ->distinct()
                ->count('eem.id');

            $exams += $blockedUpcoming > 0 ? 1 : 0;
        }

        $attendance = 0;
        if (Schema::hasTable('attendance_summaries')) {
            $attendance = (int) DB::table('attendance_summaries')
                ->where('student_id', $student->id)
                ->where('attendance_percentage', '<', 90)
                ->count();
        }

        $eligibility = $attendance;
        if ($needsClearance) {
            $eligibility = max($eligibility, 1);
        }

        $assessments = 0;
        if (Schema::hasTable('objective_assessments') && Schema::hasTable('objective_submissions')) {
            $now = now();
            $openAssessments = DB::table('objective_assessments as oa')
                ->whereIn('oa.status', ['published', 'ready'])
                ->where(function ($q) use ($now) {
                    $q->whereNull('oa.available_from')->orWhere('oa.available_from', '<=', $now);
                })
                ->where(function ($q) use ($now) {
                    $q->whereNull('oa.available_until')->orWhere('oa.available_until', '>=', $now);
                })
                ->pluck('oa.id');

            if ($openAssessments->isNotEmpty()) {
                $done = DB::table('objective_submissions')
                    ->where('student_id', $student->id)
                    ->whereIn('objective_assessment_id', $openAssessments)
                    ->whereNotNull('student_submitted_at')
                    ->pluck('objective_assessment_id')
                    ->unique();

                $assessments = $openAssessments->diff($done)->count();
            }
        }

        $suggestions = 0;
        if (Schema::hasTable('student_suggestions')) {
            $suggestions = (int) DB::table('student_suggestions')
                ->where('student_id', $student->id)
                ->whereIn('status', ['open', 'under_review'])
                ->whereNotNull('response')
                ->where('updated_at', '>=', now()->subDays(14))
                ->count();
        }

        $examRequests = 0;
        if (Schema::hasTable('special_exam_requests')) {
            $examRequests += SpecialExamRequest::query()
                ->where('student_id', $student->id)
                ->whereIn('status', ['pending', 'on_hold'])
                ->count();
        }
        if (Schema::hasTable('supplementary_requests')) {
            $examRequests += SupplementaryExamRequest::query()
                ->where('student_id', $student->id)
                ->whereIn('application_status', ['pending_review', 'pending_fee', 'on_hold'])
                ->count();
        }

        $registeredUnitIds = collect();
        if (Schema::hasTable('registered_units') && Schema::hasTable('student_semester_registrations')) {
            $registeredUnitIds = DB::table('registered_units as ru')
                ->join('student_semester_registrations as ssr', 'ssr.id', '=', 'ru.semester_registration_id')
                ->where('ssr.student_id', $student->id)
                ->pluck('ru.unit_id')
                ->filter()
                ->unique()
                ->values();
        }

        $units = 0;
        if ($registeredUnitIds->isEmpty()
            && in_array($student->enrollment_status, ['active', 'enrolled', 'registered'], true)
            && Schema::hasTable('curriculum_version_units')) {
            $units = 1;
        }

        $content = 0;
        if (Schema::hasTable('unit_contents') && $registeredUnitIds->isNotEmpty()) {
            $content = (int) DB::table('unit_contents')
                ->whereIn('unit_id', $registeredUnitIds)
                ->where('status', 'published')
                ->where('created_at', '>=', now()->subDays(14))
                ->count();
        }

        $progress = 0;
        if (Schema::hasTable('exam_results')) {
            $progressQuery = DB::table('exam_results')->where('student_id', $student->id);

            if (Schema::hasColumn('exam_results', 'result_status')) {
                $progressQuery->where(function ($q) {
                    $q->whereIn('result_status', ['failed', 'incomplete', 'supplementary']);
                });
            } else {
                $progressQuery->where(function ($q) {
                    $q->where('is_supplementary', 1)
                        ->orWhere('supplementary_triggered', 1)
                        ->orWhere('clinical_supplementary_triggered', 1);

                    if (Schema::hasColumn('exam_results', 'grade_letter')) {
                        $q->orWhereIn('grade_letter', ['E', 'F']);
                    }
                });
            }

            $progress = (int) $progressQuery->count();
        }

        $calendar = 0;
        if (Schema::hasTable('curriculum_version_periods') && $student->program_id) {
            $calendar = (int) DB::table('curriculum_version_periods as cvp')
                ->join('curriculum_versions as cv', 'cv.id', '=', 'cvp.curriculum_version_id')
                ->where('cv.program_id', $student->program_id)
                ->whereNotNull('cvp.end_date')
                ->whereDate('cvp.end_date', '>=', now()->toDateString())
                ->whereDate('cvp.end_date', '<=', now()->addDays(14)->toDateString())
                ->count();
            $calendar = min(1, $calendar);
        }

        $registration = 0;
        if (Schema::hasTable('student_semester_registrations')) {
            $openReg = DB::table('student_semester_registrations')
                ->where('student_id', $student->id)
                ->whereIn('status', ['draft', 'pending', 'returned', 'incomplete'])
                ->count();
            $registration = $openReg;
            if ($registration === 0 && $registeredUnitIds->isEmpty()
                && in_array($student->enrollment_status, ['active', 'enrolled', 'registered'], true)) {
                $registration = 1;
            }
        }

        $timetableLesson = 0;
        $timetableExam = 0;
        if (Schema::hasTable('program_timetables') && $student->program_id) {
            $base = ProgramTimetable::query()
                ->where('program_id', $student->program_id)
                ->where('status', 'published');

            $timetableLesson = (clone $base)->where('timetable_kind', 'lesson')->exists() ? 1 : 0;
            $timetableExam = (clone $base)->whereIn('timetable_kind', ['exam', 'supplementary', 'special_exam'])->exists() ? 1 : 0;
        }

        $timetable = $timetableLesson + $timetableExam;

        $academics = $units + $content + $assessments + $exams + $examRequests
            + $progress + $eligibility + $calendar + $registration + $attendance;

        $overview = $notifications + $finance + $evaluations + $assessments + $examRequests + $clearance;

        return [
            'overview' => $overview,
            'notifications' => $notifications,
            'profile' => $profile,
            'enrolment' => $enrolment,
            'documents' => $documents,
            'requests' => $requests,
            'clearance' => $clearance,
            'evaluations' => $evaluations,
            'finance' => $finance,
            'account' => $account,
            'academics' => $academics,
            'academics.units' => $units,
            'academics.content' => $content,
            'academics.assessments' => $assessments,
            'academics.exams' => $exams,
            'academics.exam-requests' => $examRequests,
            'academics.progress' => $progress,
            'academics.eligibility' => $eligibility,
            'academics.calendar' => $calendar,
            'academics.registration' => $registration,
            'academics.attendance' => $attendance,
            'timetable' => $timetable,
            'timetable.lesson' => $timetableLesson,
            'timetable.exam' => $timetableExam,
            'suggestions' => $suggestions,
        ];
    }
}
