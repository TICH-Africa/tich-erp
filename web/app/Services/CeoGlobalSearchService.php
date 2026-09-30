<?php

namespace App\Services;

use App\Models\AcademicProgram;
use App\Models\Administration\BudgetRequest;
use App\Models\Applicant;
use App\Models\CurriculumVersion;
use App\Models\Department;
use App\Models\JobVacancy;
use App\Models\Me\MeQuarterlyReport;
use App\Models\ProcurementRequisition;
use App\Models\Qa\IqaAssessment;
use App\Models\Staff;
use App\Models\Student;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class CeoGlobalSearchService
{
    /**
     * @return list<array{type: string, category: string, title: string, subtitle: string|null, href: string, score: int}>
     */
    public function search(string $query, int $limit = 40): array
    {
        $term = trim($query);
        if (mb_strlen($term) < 2) {
            return [];
        }

        $like = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $term).'%';
        $results = collect();

        $results = $results->merge($this->searchPages($term));
        $results = $results->merge($this->searchStaff($like, $term));
        $results = $results->merge($this->searchStudents($like, $term));
        $results = $results->merge($this->searchDepartments($like, $term));
        $results = $results->merge($this->searchPrograms($like, $term));
        $results = $results->merge($this->searchBudgets($like, $term));
        $results = $results->merge($this->searchProcurement($like, $term));
        $results = $results->merge($this->searchCurriculum($like, $term));
        $results = $results->merge($this->searchQuality($like, $term));
        $results = $results->merge($this->searchVacancies($like, $term));
        $results = $results->merge($this->searchApplicants($like, $term));
        $results = $results->merge($this->searchMeReports($like, $term));

        return $results
            ->sortByDesc('score')
            ->take($limit)
            ->values()
            ->all();
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function searchPages(string $term): Collection
    {
        $pages = [
            [
                'title' => 'Institution dashboard',
                'keywords' => 'overview home command center kpi stats analytics',
                'href' => route('ceo.dashboard'),
                'subtitle' => 'CEO overview',
            ],
            [
                'title' => 'Budget authorizations',
                'keywords' => 'budget executive review authorize finance department',
                'href' => route('ceo.budgets.index'),
                'subtitle' => 'Executive approvals',
            ],
            [
                'title' => 'Approval workflow',
                'keywords' => 'workflow route finance return reject review',
                'href' => route('ceo.approvals.index'),
                'subtitle' => 'Budget routing',
            ],
            [
                'title' => 'Procurement',
                'keywords' => 'requisition purchase ceo approval supplier',
                'href' => route('ceo.procurement.index'),
                'subtitle' => 'CEO procurement queue',
            ],
            [
                'title' => 'Curriculum sign-off',
                'keywords' => 'curriculum programme version publish academics',
                'href' => route('ceo.curriculum.index'),
                'subtitle' => 'Pending CEO curriculum',
            ],
            [
                'title' => 'Academics hub',
                'keywords' => 'academics programmes units learning departments',
                'href' => route('ceo.academics.index'),
                'subtitle' => 'Academics overview',
            ],
            [
                'title' => 'Quality reports',
                'keywords' => 'iqa quality audit assessment published',
                'href' => route('ceo.quality.index'),
                'subtitle' => 'Published IQA assessments',
            ],
            [
                'title' => 'M&E reports',
                'keywords' => 'monitoring evaluation quarterly pime health',
                'href' => route('ceo.me.index'),
                'subtitle' => 'Quarterly M&E packs',
            ],
            [
                'title' => 'Financial policy',
                'keywords' => 'finance policy sign digital signature',
                'href' => route('ceo.finance-policy.index'),
                'subtitle' => 'Policy sign-off',
            ],
            [
                'title' => 'M&E policy',
                'keywords' => 'me policy monitoring evaluation sign',
                'href' => route('ceo.me-policy.index'),
                'subtitle' => 'Policy sign-off',
            ],
            [
                'title' => 'Workforce',
                'keywords' => 'staff headcount leave vacancy recruitment performance hr',
                'href' => route('ceo.dashboard').'#workforce',
                'subtitle' => 'Dashboard · workforce',
            ],
            [
                'title' => 'Students & admissions',
                'keywords' => 'students enrollments admissions applicants pipeline',
                'href' => route('ceo.dashboard').'#students',
                'subtitle' => 'Dashboard · students',
            ],
            [
                'title' => 'Finance snapshot',
                'keywords' => 'revenue expenditure net income receivables treasury invoices',
                'href' => route('ceo.dashboard').'#finance',
                'subtitle' => 'Dashboard · finance',
            ],
        ];

        $needle = Str::lower($term);

        return collect($pages)
            ->map(function (array $page) use ($needle) {
                $haystack = Str::lower($page['title'].' '.$page['keywords'].' '.$page['subtitle']);
                if (! str_contains($haystack, $needle)) {
                    return null;
                }

                $score = str_contains(Str::lower($page['title']), $needle) ? 100 : 70;

                return [
                    'type' => 'page',
                    'category' => 'Pages & modules',
                    'title' => $page['title'],
                    'subtitle' => $page['subtitle'],
                    'href' => $page['href'],
                    'score' => $score,
                ];
            })
            ->filter()
            ->values();
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function searchStaff(string $like, string $term): Collection
    {
        if (! Schema::hasTable('staff')) {
            return collect();
        }

        $query = Staff::query();
        if (method_exists(Staff::class, 'scopeExcludePlatformOperators')) {
            $query->excludePlatformOperators();
        }

        $rows = $query
            ->where(function ($q) use ($like) {
                $q->where('first_name', 'like', $like)
                    ->orWhere('surname', 'like', $like)
                    ->orWhere('employee_number', 'like', $like)
                    ->orWhere('job_title', 'like', $like)
                    ->orWhere('primary_email', 'like', $like)
                    ->orWhere('organisation_email', 'like', $like);
            })
            ->with('department')
            ->limit(8)
            ->get();

        return $rows->map(function (Staff $staff) use ($term) {
            $name = trim(($staff->first_name ?? '').' '.($staff->surname ?? ''));

            return [
                'type' => 'staff',
                'category' => 'Staff',
                'title' => $name !== '' ? $name : ($staff->employee_number ?? 'Staff #'.$staff->id),
                'subtitle' => trim(($staff->job_title ?? '').' · '.($staff->department?->dept_name ?? '').' · '.($staff->employment_status ?? ''), ' ·'),
                'href' => route('ceo.dashboard').'?q='.urlencode($term).'#workforce',
                'score' => $this->scoreMatch($term, $name.' '.$staff->employee_number.' '.$staff->job_title),
            ];
        });
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function searchStudents(string $like, string $term): Collection
    {
        if (! Schema::hasTable('students')) {
            return collect();
        }

        $rows = Student::query()
            ->where(function ($q) use ($like) {
                $q->where('first_name', 'like', $like)
                    ->orWhere('surname', 'like', $like)
                    ->orWhere('registration_number', 'like', $like);
                if (Schema::hasColumn('students', 'middle_name')) {
                    $q->orWhere('middle_name', 'like', $like);
                }
            })
            ->limit(8)
            ->get();

        return $rows->map(function (Student $student) use ($term) {
            $name = method_exists($student, 'displayName')
                ? $student->displayName()
                : trim(($student->first_name ?? '').' '.($student->surname ?? ''));

            return [
                'type' => 'student',
                'category' => 'Students',
                'title' => $name !== '' ? $name : ($student->registration_number ?? 'Student #'.$student->id),
                'subtitle' => trim(($student->registration_number ?? '').' · '.($student->enrollment_status ?? ''), ' ·'),
                'href' => route('ceo.dashboard').'?q='.urlencode($term).'#students',
                'score' => $this->scoreMatch($term, $name.' '.$student->registration_number),
            ];
        });
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function searchDepartments(string $like, string $term): Collection
    {
        if (! Schema::hasTable('departments')) {
            return collect();
        }

        return Department::query()
            ->where(function ($q) use ($like) {
                $q->where('dept_name', 'like', $like)->orWhere('dept_code', 'like', $like);
            })
            ->limit(6)
            ->get()
            ->map(fn (Department $dept) => [
                'type' => 'department',
                'category' => 'Departments',
                'title' => $dept->dept_name,
                'subtitle' => $dept->dept_code,
                'href' => route('ceo.dashboard').'#workforce',
                'score' => $this->scoreMatch($term, $dept->dept_name.' '.$dept->dept_code),
            ]);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function searchPrograms(string $like, string $term): Collection
    {
        if (! Schema::hasTable('academic_programs')) {
            return collect();
        }

        return AcademicProgram::query()
            ->where(function ($q) use ($like) {
                $q->where('program_name', 'like', $like)->orWhere('program_code', 'like', $like);
            })
            ->limit(6)
            ->get()
            ->map(fn (AcademicProgram $program) => [
                'type' => 'program',
                'category' => 'Programmes',
                'title' => $program->program_name,
                'subtitle' => trim(($program->program_code ?? '').' · '.($program->status ?? ''), ' ·'),
                'href' => route('ceo.academics.index'),
                'score' => $this->scoreMatch($term, $program->program_name.' '.$program->program_code),
            ]);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function searchBudgets(string $like, string $term): Collection
    {
        if (! Schema::hasTable('admin_budget_requests')) {
            return collect();
        }

        return BudgetRequest::query()
            ->with('department')
            ->where(function ($q) use ($like) {
                $q->where('title', 'like', $like)
                    ->orWhere('request_code', 'like', $like)
                    ->orWhere('status', 'like', $like);
            })
            ->orderByDesc('id')
            ->limit(8)
            ->get()
            ->map(function (BudgetRequest $budget) use ($term) {
                $canShow = in_array($budget->status, ['executive_review', 'submitted', 'draft', 'finance_review', 'approved'], true);

                return [
                    'type' => 'budget',
                    'category' => 'Budgets',
                    'title' => $budget->title ?: $budget->request_code,
                    'subtitle' => trim(($budget->request_code ?? '').' · '.str_replace('_', ' ', (string) $budget->status).' · '.($budget->department?->dept_name ?? ''), ' ·'),
                    'href' => $canShow
                        ? route('ceo.budgets.show', $budget)
                        : route('ceo.budgets.index'),
                    'score' => $this->scoreMatch($term, $budget->title.' '.$budget->request_code),
                ];
            });
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function searchProcurement(string $like, string $term): Collection
    {
        if (! Schema::hasTable('procurement_requisitions')) {
            return collect();
        }

        return ProcurementRequisition::query()
            ->with('department')
            ->where(function ($q) use ($like) {
                $q->where('requisition_number', 'like', $like)
                    ->orWhere('requested_item', 'like', $like)
                    ->orWhere('justification', 'like', $like)
                    ->orWhere('budget_code', 'like', $like)
                    ->orWhere('status', 'like', $like);
            })
            ->orderByDesc('id')
            ->limit(8)
            ->get()
            ->map(fn (ProcurementRequisition $req) => [
                'type' => 'procurement',
                'category' => 'Procurement',
                'title' => $req->requisition_number,
                'subtitle' => trim(($req->requested_item ?: 'Requisition').' · '.str_replace('_', ' ', (string) $req->status).' · '.($req->department?->dept_name ?? ''), ' ·'),
                'href' => route('ceo.procurement.show', $req),
                'score' => $this->scoreMatch($term, $req->requisition_number.' '.$req->requested_item.' '.$req->justification),
            ]);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function searchCurriculum(string $like, string $term): Collection
    {
        if (! Schema::hasTable('curriculum_versions')) {
            return collect();
        }

        return CurriculumVersion::query()
            ->with('program')
            ->where(function ($q) use ($like) {
                $q->where('version_label', 'like', $like)
                    ->orWhere('status', 'like', $like)
                    ->orWhere('notes', 'like', $like);
            })
            ->orderByDesc('id')
            ->limit(6)
            ->get()
            ->map(fn (CurriculumVersion $version) => [
                'type' => 'curriculum',
                'category' => 'Curriculum',
                'title' => $version->version_label,
                'subtitle' => trim(($version->program?->program_name ?? '').' · '.str_replace('_', ' ', (string) $version->status), ' ·'),
                'href' => in_array($version->status, ['pending_ceo', 'published'], true)
                    ? route('ceo.curriculum.show', $version)
                    : route('ceo.curriculum.index'),
                'score' => $this->scoreMatch($term, $version->version_label.' '.$version->program?->program_name),
            ]);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function searchQuality(string $like, string $term): Collection
    {
        if (! Schema::hasTable('iqa_assessments')) {
            return collect();
        }

        return IqaAssessment::query()
            ->where(function ($q) use ($like) {
                $q->where('title', 'like', $like)
                    ->orWhere('status', 'like', $like)
                    ->orWhere('publisher_name', 'like', $like);
            })
            ->orderByDesc('id')
            ->limit(6)
            ->get()
            ->map(fn (IqaAssessment $assessment) => [
                'type' => 'quality',
                'category' => 'Quality / IQA',
                'title' => $assessment->title,
                'subtitle' => trim('#'.$assessment->id.' · '.($assessment->assessment_year ?: '—').' · '.$assessment->status, ' ·'),
                'href' => $assessment->isPublished()
                    ? route('ceo.quality.show', $assessment)
                    : route('ceo.quality.index'),
                'score' => $this->scoreMatch($term, $assessment->title.' '.$assessment->publisher_name),
            ]);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function searchVacancies(string $like, string $term): Collection
    {
        if (! Schema::hasTable('job_vacancies')) {
            return collect();
        }

        return JobVacancy::query()
            ->with('department')
            ->where(function ($q) use ($like) {
                $q->where('job_title', 'like', $like)
                    ->orWhere('vacancy_number', 'like', $like)
                    ->orWhere('employment_type', 'like', $like);
            })
            ->orderByDesc('id')
            ->limit(6)
            ->get()
            ->map(fn (JobVacancy $vacancy) => [
                'type' => 'vacancy',
                'category' => 'Vacancies',
                'title' => $vacancy->job_title,
                'subtitle' => trim(($vacancy->vacancy_number ?? '').' · '.($vacancy->department?->dept_name ?? '').' · '.(($vacancy->is_published && ! $vacancy->is_closed) ? 'open' : 'closed/draft'), ' ·'),
                'href' => route('ceo.dashboard').'#workforce',
                'score' => $this->scoreMatch($term, $vacancy->job_title.' '.$vacancy->vacancy_number),
            ]);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function searchApplicants(string $like, string $term): Collection
    {
        if (! Schema::hasTable('applicants')) {
            return collect();
        }

        $query = Applicant::query()->where(function ($q) use ($like) {
            if (Schema::hasColumn('applicants', 'first_name')) {
                $q->where('first_name', 'like', $like)->orWhere('surname', 'like', $like);
            }
            if (Schema::hasColumn('applicants', 'email')) {
                $q->orWhere('email', 'like', $like);
            }
            if (Schema::hasColumn('applicants', 'application_number')) {
                $q->orWhere('application_number', 'like', $like);
            }
            if (Schema::hasColumn('applicants', 'status')) {
                $q->orWhere('status', 'like', $like);
            }
        });

        return $query->orderByDesc('id')->limit(6)->get()->map(function (Applicant $applicant) use ($term) {
            $name = trim(($applicant->first_name ?? '').' '.($applicant->surname ?? ''));

            return [
                'type' => 'applicant',
                'category' => 'Admissions',
                'title' => $name !== '' ? $name : ('Applicant #'.$applicant->id),
                'subtitle' => trim(($applicant->application_number ?? '').' · '.str_replace('_', ' ', (string) ($applicant->status ?? '')), ' ·'),
                'href' => route('ceo.dashboard').'#students',
                'score' => $this->scoreMatch($term, $name.' '.($applicant->application_number ?? '')),
            ];
        });
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function searchMeReports(string $like, string $term): Collection
    {
        if (! Schema::hasTable('me_quarterly_reports')) {
            return collect();
        }

        return MeQuarterlyReport::query()
            ->with('department')
            ->where(function ($outer) use ($like) {
                $outer->where('status', 'like', $like);
                if (Schema::hasColumn('me_quarterly_reports', 'ceo_notes')) {
                    $outer->orWhere('ceo_notes', 'like', $like);
                }
                $outer->orWhereHas('department', function ($q) use ($like) {
                    $q->where('dept_name', 'like', $like)->orWhere('dept_code', 'like', $like);
                });
            })
            ->orderByDesc('id')
            ->limit(6)
            ->get()
            ->map(fn (MeQuarterlyReport $report) => [
                'type' => 'me_report',
                'category' => 'M&E reports',
                'title' => ($report->department?->dept_name ?? 'Department').' quarterly report',
                'subtitle' => str_replace('_', ' ', (string) $report->status),
                'href' => in_array($report->status, ['ceo_delivered', 'me_verified'], true)
                    ? route('ceo.me.show', $report)
                    : route('ceo.me.index'),
                'score' => $this->scoreMatch($term, $report->department?->dept_name.' '.$report->status),
            ]);
    }

    private function scoreMatch(string $term, ?string $haystack): int
    {
        $hay = Str::lower((string) $haystack);
        $needle = Str::lower($term);

        if ($hay === $needle) {
            return 120;
        }
        if (str_starts_with($hay, $needle)) {
            return 95;
        }
        if (str_contains($hay, $needle)) {
            return 75;
        }

        return 40;
    }
}
