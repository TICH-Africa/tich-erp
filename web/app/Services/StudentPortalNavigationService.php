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
            'enrolment' => 'Enrolment',
            'documents' => 'Documents',
            'requests' => 'Lifecycle requests',
            'exam-requests' => 'Exam sitting requests',
            'evaluations' => 'Course evaluations',
            'notifications' => 'Notifications',
            'clearance' => 'Clearance',
            'academics' => 'Academics',
            'timetable' => 'Timetable',
            'finance' => 'Finance',
            'suggestions' => 'Suggestion box',
            'account' => 'Account',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function academicsTabs(): array
    {
        return [
            'units' => 'My Units',
            'content' => 'Learning Content',
            'assessments' => 'Assessments',
            'exams' => 'Exams & Grades',
            'progress' => 'Academic progress',
            'eligibility' => 'Exam eligibility',
            'calendar' => 'Academic calendar',
            'registration' => 'Unit registration',
            'attendance' => 'Attendance',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function timetableTabs(): array
    {
        return [
            'lesson' => 'Lesson Timetable',
            'exam' => 'Exam Timetable',
        ];
    }

    public function resolveSection(Request $request): string
    {
        $section = $request->string('section')->toString() ?: 'overview';

        return array_key_exists($section, $this->sections()) ? $section : 'overview';
    }

    public function resolveTab(Request $request, string $section): ?string
    {
        $tab = $request->string('tab')->toString();

        return match ($section) {
            'academics' => array_key_exists($tab, $this->academicsTabs()) ? $tab : 'units',
            'timetable' => array_key_exists($tab, $this->timetableTabs()) ? $tab : 'lesson',
            default => null,
        };
    }

    public function portalTitle(string $section, ?string $tab = null): string
    {
        $label = $this->sections()[$section] ?? 'Overview';

        if ($section === 'academics' && $tab) {
            $label = $this->academicsTabs()[$tab] ?? $label;
        }

        if ($section === 'timetable' && $tab) {
            $label = $this->timetableTabs()[$tab] ?? $label;
        }

        return $label.' - Student portal';
    }

    /**
     * @return list<array<string, mixed>>
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
                'label' => 'Lifecycle requests',
                'section' => 'requests',
            ],
            [
                'type' => 'link',
                'label' => 'Exam sitting requests',
                'section' => 'exam-requests',
            ],
            [
                'type' => 'link',
                'label' => 'Clearance',
                'section' => 'clearance',
            ],
            [
                'type' => 'link',
                'label' => 'Notifications',
                'section' => 'notifications',
            ],
            [
                'type' => 'group',
                'label' => 'Academics',
                'icon' => 'graduation-cap',
                'section' => 'academics',
                'items' => [
                    [
                        'label' => 'My Units',
                        'tab' => 'units',
                        'icon' => 'book-open',
                    ],
                    [
                        'label' => 'Learning Content',
                        'tab' => 'content',
                        'icon' => 'layers',
                    ],
                    [
                        'label' => 'Assessments',
                        'tab' => 'assessments',
                        'icon' => 'clipboard-check',
                    ],
                    [
                        'label' => 'Exams & Grades',
                        'tab' => 'exams',
                        'icon' => 'award',
                    ],
                    [
                        'label' => 'Academic progress',
                        'tab' => 'progress',
                        'icon' => 'bar-chart',
                    ],
                    [
                        'label' => 'Exam eligibility',
                        'tab' => 'eligibility',
                        'icon' => 'check-circle',
                    ],
                    [
                        'label' => 'Academic calendar',
                        'tab' => 'calendar',
                        'icon' => 'calendar',
                    ],
                    [
                        'label' => 'Unit registration',
                        'tab' => 'registration',
                        'icon' => 'list',
                    ],
                    [
                        'label' => 'Attendance',
                        'tab' => 'attendance',
                        'icon' => 'clipboard-check',
                    ],
                ],
            ],
            [
                'type' => 'group',
                'label' => 'Timetable',
                'icon' => 'calendar',
                'section' => 'timetable',
                'items' => [
                    [
                        'label' => 'Lesson Timetable',
                        'tab' => 'lesson',
                        'icon' => 'calendar',
                    ],
                    [
                        'label' => 'Exam Timetable',
                        'tab' => 'exam',
                        'icon' => 'file-text',
                    ],
                ],
            ],
            [
                'type' => 'link',
                'label' => 'Course evaluations',
                'section' => 'evaluations',
            ],
            [
                'type' => 'link',
                'label' => 'Finance',
                'section' => 'finance',
            ],
            [
                'type' => 'link',
                'label' => 'Suggestion box',
                'section' => 'suggestions',
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
                'label' => 'Enrolment',
                'description' => 'Campus, intake, admission date, and fee clearance information.',
                'section' => 'enrolment',
                'group' => 'services',
            ],
            [
                'label' => 'Documents',
                'description' => 'Certificates and files submitted with your application, plus document requests.',
                'section' => 'documents',
                'group' => 'services',
            ],
            [
                'label' => 'Lifecycle requests',
                'description' => 'Request deferment, withdrawal, or readmission.',
                'section' => 'requests',
                'group' => 'services',
            ],
            [
                'label' => 'Exam sitting requests',
                'description' => 'Apply for special or supplementary exam sittings.',
                'section' => 'exam-requests',
                'group' => 'services',
            ],
            [
                'label' => 'Clearance',
                'description' => 'Track finance, library, hostel, and academic clearance status.',
                'section' => 'clearance',
                'group' => 'services',
            ],
            [
                'label' => 'Notifications',
                'description' => 'Fee, exam, and academic alerts for your account.',
                'section' => 'notifications',
                'group' => 'services',
            ],
            [
                'label' => 'Lesson Timetable',
                'description' => 'Weekly lesson schedule for your registered units.',
                'section' => 'timetable',
                'group' => 'learning',
            ],
            [
                'label' => 'My Units',
                'description' => 'Curriculum units, semester registration, and programme plan.',
                'section' => 'academics',
                'group' => 'learning',
            ],
            [
                'label' => 'Course evaluations',
                'description' => 'Submit course and lecturer evaluations when windows are open.',
                'section' => 'evaluations',
                'group' => 'learning',
            ],
            [
                'label' => 'Finance',
                'description' => 'Fee balance, invoices, payment history, and M-Pesa self-pay.',
                'section' => 'finance',
                'group' => 'learning',
            ],
            [
                'label' => 'Suggestion box',
                'description' => 'Share suggestions, comments, or complaints with Academics.',
                'section' => 'suggestions',
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
