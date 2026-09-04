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
        $sections = [
            'overview' => 'Overview',
            'units' => 'My units',
            'timetable' => 'Timetable',
            'attendance' => 'Attendance',
            'grading' => 'Marks & assessments',
            'lesson-plans' => 'Lesson plans',
            'content' => 'Learning content',
            'exam-papers' => 'Exam papers',
        ];

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
        $items = [
            ['type' => 'link', 'label' => 'Overview', 'section' => 'overview', 'icon' => 'dashboard'],
            ['type' => 'heading', 'label' => 'Teaching'],
            ['type' => 'link', 'label' => 'My units', 'section' => 'units', 'icon' => 'book-open'],
            ['type' => 'link', 'label' => 'Timetable', 'section' => 'timetable', 'icon' => 'calendar'],
            ['type' => 'link', 'label' => 'Attendance', 'section' => 'attendance', 'icon' => 'clipboard-check'],
            ['type' => 'link', 'label' => 'Marks & assessments', 'section' => 'grading', 'icon' => 'award'],
            ['type' => 'link', 'label' => 'Lesson plans', 'section' => 'lesson-plans', 'icon' => 'notebook'],
            ['type' => 'link', 'label' => 'Learning content', 'section' => 'content', 'icon' => 'layers'],
            ['type' => 'link', 'label' => 'Exam papers', 'section' => 'exam-papers', 'icon' => 'file-text'],
        ];

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

        return $user && $user->hasAnyRole(['HOD', 'Dean of Students', 'Academic Registrar', 'Super Admin']);
    }
}
