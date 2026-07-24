<?php

namespace App\Services;

use App\Models\Student;
use Illuminate\Http\Request;

class StudentPortalNavigationService
{
    /**
     * @return array<string, string>
     */
    public function sections(): array
    {
        return [
            'overview' => 'Overview',
            'profile' => 'My profile',
            'application' => 'Application',
            'enrolment' => 'Enrolment',
            'documents' => 'Documents',
            'academics' => 'Academics',
            'timetable' => 'Timetable',
            'finance' => 'Finance',
            'account' => 'Account',
        ];
    }

    public function resolveSection(Request $request): string
    {
        $section = $request->string('section')->toString() ?: 'overview';

        return array_key_exists($section, $this->sections()) ? $section : 'overview';
    }

    /**
     * @return list<array{type: string, label: string, section?: string, route?: string, params?: array<string, mixed>, coming_soon?: bool}>
     */
    public function sidebarNavigation(Student $student): array
    {
        return [
            [
                'type' => 'link',
                'label' => 'Overview',
                'section' => 'overview',
            ],
            ['type' => 'heading', 'label' => 'My services'],
            [
                'type' => 'link',
                'label' => 'My profile',
                'section' => 'profile',
            ],
            [
                'type' => 'link',
                'label' => 'Application',
                'section' => 'application',
            ],
            [
                'type' => 'link',
                'label' => 'Enrolment',
                'section' => 'enrolment',
            ],
            [
                'type' => 'link',
                'label' => 'Documents',
                'section' => 'documents',
            ],
            [
                'type' => 'link',
                'label' => 'Academics',
                'section' => 'academics',
            ],
            [
                'type' => 'link',
                'label' => 'Timetable',
                'section' => 'timetable',
            ],
            [
                'type' => 'link',
                'label' => 'Finance',
                'section' => 'finance',
            ],
            ['type' => 'heading', 'label' => 'Account'],
            [
                'type' => 'link',
                'label' => 'Security & account',
                'section' => 'account',
            ],
        ];
    }

    /**
     * @return list<array{label: string, description: string, section: string, group: string, coming_soon?: bool}>
     */
    public function modules(): array
    {
        return [
            [
                'label' => 'My profile',
                'description' => 'Personal details, contact information, and programme enrolment summary.',
                'section' => 'profile',
                'group' => 'services',
            ],
            [
                'label' => 'Application',
                'description' => 'Track your admission application, review status, and decision notes.',
                'section' => 'application',
                'group' => 'services',
            ],
            [
                'label' => 'Enrolment',
                'description' => 'Campus, intake, admission date, and fee clearance information.',
                'section' => 'enrolment',
                'group' => 'services',
            ],
            [
                'label' => 'Documents',
                'description' => 'Certificates and files submitted with your application.',
                'section' => 'documents',
                'group' => 'services',
            ],
            [
                'label' => 'Timetable',
                'description' => 'Weekly lesson, exam, and special exam schedule.',
                'section' => 'timetable',
                'group' => 'learning',
            ],
            [
                'label' => 'Academics',
                'description' => 'Unit registration, grades, attendance, and timetables.',
                'section' => 'academics',
                'group' => 'learning',
            ],
            [
                'label' => 'Finance',
                'description' => 'Fee balance, invoices, and payment history.',
                'section' => 'finance',
                'group' => 'learning',
            ],
            [
                'label' => 'Security & account',
                'description' => 'Multi-factor authentication and sign-out options.',
                'section' => 'account',
                'group' => 'account',
            ],
        ];
    }
}
