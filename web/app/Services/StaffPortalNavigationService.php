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

        if ($this->userIsTeachingStaff()) {
            $items[] = ['type' => 'link', 'label' => 'Overview', 'section' => 'overview', 'icon' => 'dashboard'];
            $items[] = ['type' => 'heading', 'label' => 'Teaching'];
            $items[] = ['type' => 'link', 'label' => 'My units', 'section' => 'units', 'icon' => 'book-open'];
            $items[] = ['type' => 'link', 'label' => 'Timetable', 'section' => 'timetable', 'icon' => 'calendar'];
            $items[] = ['type' => 'link', 'label' => 'Attendance', 'section' => 'attendance', 'icon' => 'clipboard-check'];
            $items[] = ['type' => 'link', 'label' => 'Marks & assessments', 'section' => 'grading', 'icon' => 'award'];
            $items[] = ['type' => 'link', 'label' => 'Lesson plans', 'section' => 'lesson-plans', 'icon' => 'notebook'];
            $items[] = ['type' => 'link', 'label' => 'Learning content', 'section' => 'content', 'icon' => 'layers'];
            $items[] = ['type' => 'link', 'label' => 'Exam papers', 'section' => 'exam-papers', 'icon' => 'file-text'];
        }

        if ($this->userIsHod()) {
            $items[] = ['type' => 'heading', 'label' => 'Management'];
            $items[] = [
                'type' => 'dropdown',
                'label' => 'HOD management',
                'icon' => 'users-cog',
                'children' => [
                    ['type' => 'link', 'label' => 'Overview', 'section' => 'hod-management', 'icon' => 'layout-grid'],
                    ['type' => 'link', 'label' => 'Lesson plans', 'section' => 'hod-lesson-plans', 'icon' => 'notebook'],
                    ['type' => 'link', 'label' => 'Unit allocations', 'section' => 'hod-unit-allocations', 'icon' => 'users'],
                    ['type' => 'link', 'label' => 'Workload matrix', 'section' => 'hod-workload', 'icon' => 'bar-chart'],
                    ['type' => 'link', 'label' => 'Attendance review', 'section' => 'hod-attendance', 'icon' => 'clipboard-check'],
                    ['type' => 'link', 'label' => 'Department leave', 'section' => 'hod-leave', 'icon' => 'calendar-off'],
                    ['type' => 'link', 'label' => 'Performance', 'section' => 'hod-performance', 'icon' => 'bar-chart'],
                ],
            ];
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
