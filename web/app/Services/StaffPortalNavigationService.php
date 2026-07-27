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
    public function sections(bool $isHod = false): array
    {
        $sections = [
            'units' => 'My units',
            'timetable' => 'Timetable',
            'attendance' => 'Attendance',
            'grading' => 'Performance terminal',
            'lesson-plans' => 'Lesson plans',
            'content' => 'Learning content',
        ];

        if ($isHod) {
            $sections['hod-dashboard'] = 'HOD Dashboard';
        } else {
            $sections['overview'] = 'Overview';
        }

        return $sections;
    }

    public function resolveSection(Request $request, bool $isHod = false, string $default = 'overview'): string
    {
        $section = $request->string('section')->toString() ?: $default;

        if ($section === 'hod-dashboard' && ! $isHod) {
            return $default;
        }

        return array_key_exists($section, $this->sections($isHod)) ? $section : $default;
    }

    /**
     * @return list<array{type: string, label: string, section?: string}>
     */
    public function sidebarNavigation(bool $isHod = false): array
    {
        $links = [];

        if ($isHod) {
            $links[] = ['type' => 'link', 'label' => 'HOD Dashboard', 'section' => 'hod-dashboard'];
            $links[] = ['type' => 'link', 'label' => 'My units', 'section' => 'units'];
            $links[] = ['type' => 'link', 'label' => 'Timetable', 'section' => 'timetable'];
            $links[] = ['type' => 'link', 'label' => 'Attendance', 'section' => 'attendance'];
            $links[] = ['type' => 'link', 'label' => 'Performance terminal', 'section' => 'grading'];
            $links[] = ['type' => 'link', 'label' => 'Lesson plans', 'section' => 'lesson-plans'];
            $links[] = ['type' => 'link', 'label' => 'Learning content', 'section' => 'content'];
        } else {
            $links[] = ['type' => 'link', 'label' => 'Overview', 'section' => 'overview'];
            $links[] = ['type' => 'heading', 'label' => 'Teaching'];
            $links[] = ['type' => 'link', 'label' => 'My units', 'section' => 'units'];
            $links[] = ['type' => 'link', 'label' => 'Timetable', 'section' => 'timetable'];
            $links[] = ['type' => 'link', 'label' => 'Attendance', 'section' => 'attendance'];
            $links[] = ['type' => 'link', 'label' => 'Performance terminal', 'section' => 'grading'];
            $links[] = ['type' => 'link', 'label' => 'Lesson plans', 'section' => 'lesson-plans'];
            $links[] = ['type' => 'link', 'label' => 'Learning content', 'section' => 'content'];
        }

        return $links;
    }
}
