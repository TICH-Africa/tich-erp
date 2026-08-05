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
            'documents' => 'My Documents',
            'policies' => 'HR Policies',
        ];

        if ($this->userIsHod()) {
            $sections['hod-management'] = 'HOD management';
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
            ['type' => 'link', 'label' => 'My Documents', 'section' => 'documents', 'icon' => 'folder'],
            ['type' => 'link', 'label' => 'HR Policies', 'section' => 'policies', 'icon' => 'shield-check'],
        ];

        if ($this->userIsHod()) {
            $items[] = ['type' => 'heading', 'label' => 'Management'];
            $items[] = ['type' => 'link', 'label' => 'HOD management', 'section' => 'hod-management', 'icon' => 'users-cog'];
        }

        return $items;
    }

    private function userIsHod(): bool
    {
        $user = Auth::user();

        return $user && $user->hasAnyRole(['HOD', 'Dean', 'Academic Registrar', 'Super Admin']);
    }
}
