<?php

namespace App\Services;

use App\Models\ProgramTimetableSession;
use App\Models\Staff;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class StaffPortalNavigationService
{
    /**
     * @return array<string, string>
     */
    public function sections(): array
    {
        $sections = [];

        if ($this->userIsTeachingStaff()) {
            $sections['overview'] = 'Overview';
            $sections['units'] = 'My units';
            $sections['timetable'] = 'Timetable';
            $sections['attendance'] = 'Attendance';
            $sections['grading'] = 'Marks & assessments';
            $sections['lesson-plans'] = 'Lesson plans';
            $sections['content'] = 'Learning content';
            $sections['exam-papers'] = 'Exam papers';
        }

        if ($this->userIsHod()) {
            $sections['hod-management'] = 'HOD management';
            $sections['hod-lesson-plans'] = 'Lesson plans';
            $sections['hod-unit-allocations'] = 'Unit allocations';
            $sections['hod-workload'] = 'Workload matrix';
            $sections['hod-workplans'] = 'Semester workplans';
            $sections['hod-attendance'] = 'Attendance review';
            $sections['hod-leave'] = 'Department leave';
            $sections['hod-performance'] = 'Performance';
        }

        return $sections;
    }

    public function resolveSection(Request $request): string
    {
        $section = $request->string('section')->toString() ?: 'overview';

        return array_key_exists($section, $this->sections()) ? $section : 'overview';
    }

    /**
     * @return list<array{type: string, label: string, section?: string}>
     */
    public function sidebarNavigation(): array
    {
        $items = [];
        $isTeachingStaff = $this->userIsTeachingStaff();
        $isHod = $this->userIsHod();

        if ($isTeachingStaff) {
            $items[] = ['type' => 'link', 'label' => 'Overview', 'section' => 'overview', 'icon' => 'dashboard'];
            $items[] = ['type' => 'heading', 'label' => 'Teaching'];
            $items[] = ['type' => 'link', 'label' => 'My units', 'section' => 'units', 'icon' => 'book-open'];
            $items[] = ['type' => 'link', 'label' => 'Timetable', 'section' => 'timetable', 'icon' => 'calendar'];
            $items[] = ['type' => 'link', 'label' => 'Attendance', 'section' => 'attendance', 'icon' => 'clipboard-check'];
            $items[] = ['type' => 'link', 'label' => 'Marks & assessments', 'section' => 'grading', 'icon' => 'award'];
            $items[] = ['type' => 'link', 'label' => 'Lesson plans', 'section' => 'lesson-plans', 'icon' => 'notebook'];
            $items[] = ['type' => 'link', 'label' => 'Learning content', 'section' => 'content', 'icon' => 'layers'];
            $items[] = ['type' => 'link', 'label' => 'Exam papers', 'section' => 'exam-papers', 'icon' => 'file-text'];
            $items = array_merge($items, $this->planningNavigation($isHod, withTechnicalPlans: true));
        }

        if ($isHod) {
            $items[] = ['type' => 'heading', 'label' => 'HOD management'];

            foreach ([
                ['label' => 'Overview', 'section' => 'hod-management', 'icon' => 'layout-grid'],
                ['label' => 'Lesson plans', 'section' => 'hod-lesson-plans', 'icon' => 'notebook'],
                ['label' => 'Unit allocations', 'section' => 'hod-unit-allocations', 'icon' => 'users'],
                ['label' => 'Workload matrix', 'section' => 'hod-workload', 'icon' => 'bar-chart'],
                ['label' => 'Semester workplans', 'section' => 'hod-workplans', 'icon' => 'file-text'],
                ['label' => 'Attendance review', 'section' => 'hod-attendance', 'icon' => 'clipboard-check'],
                ['label' => 'Department leave', 'section' => 'hod-leave', 'icon' => 'calendar-off'],
                ['label' => 'Performance', 'section' => 'hod-performance', 'icon' => 'bar-chart'],
            ] as $child) {
                $items[] = ['type' => 'link'] + $child;
            }

            if (! $isTeachingStaff) {
                $items = array_merge($items, $this->planningNavigation(true, withTechnicalPlans: false));
            }
        }

        return $items;
    }

    /**
     * @return list<array<string, string>>
     */
    private function planningNavigation(bool $departmentScoped, bool $withTechnicalPlans): array
    {
        if (! \Illuminate\Support\Facades\Route::has('academics.budgeting.index')) {
            return [];
        }

        $items = [
            ['type' => 'heading', 'label' => 'Planning & budget'],
            ['type' => 'route', 'label' => $departmentScoped ? 'Department budget' : 'My budget', 'route' => 'academics.budgeting.index', 'icon' => 'wallet'],
        ];

        if ($withTechnicalPlans) {
            $items[] = ['type' => 'route', 'label' => 'Technical plan', 'route' => 'academics.technical-plans.index', 'icon' => 'file-text'];
            if (\Illuminate\Support\Facades\Route::has('academics.finance-policy.sign')) {
                $items[] = ['type' => 'route', 'label' => 'Sign financial policy', 'route' => 'academics.finance-policy.sign', 'icon' => 'shield'];
            }
        }

        return $items;
    }

    private function userIsHod(): bool
    {
        $user = Auth::user();

        return $user && $user->hasAnyRole(['HOD', 'Super Admin']);
    }

    private function userIsTeachingStaff(): bool
    {
        $user = Auth::user();

        if (! $user) {
            return false;
        }

        if ($user->hasRole('Lecturer/Tutor')) {
            return true;
        }

        $staff = app(StaffPortalService::class)->staffForUser($user);

        return $staff && $staff->is_teaching_staff;
    }
}
