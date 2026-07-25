<?php

namespace App\Services;

use App\Models\ProgramTimetableSession;
use App\Models\Staff;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class StaffPortalNavigationService
{
    /**
     * @return array<string, string>
     */
    public function sections(): array
    {
        return [
            'overview' => 'Overview',
            'units' => 'My units',
            'timetable' => 'Timetable',
            'attendance' => 'Attendance',
            'grading' => 'Assessment & grading',
            'lesson-plans' => 'Lesson plans',
            'content' => 'Learning content',
        ];
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
        return [
            ['type' => 'link', 'label' => 'Overview', 'section' => 'overview'],
            ['type' => 'heading', 'label' => 'Teaching'],
            ['type' => 'link', 'label' => 'My units', 'section' => 'units'],
            ['type' => 'link', 'label' => 'Timetable', 'section' => 'timetable'],
            ['type' => 'link', 'label' => 'Attendance', 'section' => 'attendance'],
            ['type' => 'link', 'label' => 'Assessment & grading', 'section' => 'grading'],
            ['type' => 'link', 'label' => 'Lesson plans', 'section' => 'lesson-plans'],
            ['type' => 'link', 'label' => 'Learning content', 'section' => 'content'],
        ];
    }
}
