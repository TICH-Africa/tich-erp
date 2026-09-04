<?php

namespace App\Services\Sidebar;

use App\Models\CourseEvaluation;
use App\Models\CourseEvaluationWindow;
use App\Models\Student;
use App\Models\StudentClearanceItem;
use App\Models\StudentDocumentRequest;
use App\Models\StudentLifecycleRequest;
use App\Models\StudentNotification;
use App\Models\StudentProfileChangeRequest;
use App\Models\StudentTranscriptRequest;
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
        'notifications' => 'Notifications',
        'profile' => 'My profile',
        'documents' => 'Documents',
        'requests' => 'Deferment',
        'clearance' => 'Clearance',
        'evaluations' => 'Course evaluations',
        'finance' => 'Finance',
        'academics' => 'Academics',
        'academics.exams' => 'Exams & Grades',
        'academics.exam-requests' => 'Supplementary & Special Exams',
        'academics.assessments' => 'Assessments',
        'academics.eligibility' => 'Exam eligibility',
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
            'notifications',
            'profile',
            'documents',
            'requests',
            'clearance',
            'evaluations',
            'finance',
            'academics',
            'suggestions' => $section,
            default => null,
        };
    }

    public function badgeKeyForAcademicsTab(string $tab): ?string
    {
        return match ($tab) {
            'exams' => 'academics.exams',
            'exam-requests' => 'academics.exam-requests',
            'assessments' => 'academics.assessments',
            'eligibility' => 'academics.eligibility',
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
        $finance = 0;
        if ($outstanding > 0 || $needsClearance) {
            $finance = 1;
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

        $eligibility = 0;
        if (Schema::hasTable('attendance_summaries')) {
            $eligibility = (int) DB::table('attendance_summaries')
                ->where('student_id', $student->id)
                ->where('attendance_percentage', '<', 90)
                ->count();
        }
        if ($needsClearance) {
            $eligibility = max($eligibility, 1);
        }

        $assessments = 0;
        if (Schema::hasTable('objective_assessments') && Schema::hasTable('objective_submissions')) {
            $now = now();
            $openAssessments = DB::table('objective_assessments as oa')
                ->where('oa.status', 'published')
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
                    ->whereIn('assessment_id', $openAssessments)
                    ->whereNotNull('student_submitted_at')
                    ->pluck('assessment_id')
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

        $academics = $exams + $assessments + $eligibility;

        return [
            'notifications' => $notifications,
            'profile' => $profile,
            'documents' => $documents,
            'requests' => $requests,
            'clearance' => $clearance,
            'evaluations' => $evaluations,
            'finance' => $finance,
            'academics' => $academics,
            'academics.exams' => $exams,
            'academics.assessments' => $assessments,
            'academics.eligibility' => $eligibility,
            'suggestions' => $suggestions,
        ];
    }
}
