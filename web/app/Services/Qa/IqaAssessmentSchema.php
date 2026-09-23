<?php

namespace App\Services\Qa;

/**
 * NATIONAL POLYTECHNIC QUALITY AUDIT TOOL — structure copied from the official form.
 * Checklist columns: S/No. | Audit Area | Indicator/Question Target | Observations/Remarks | Recommendations
 * (no separate Target column; S/No holds area codes e.g. 1.1; Audit Area is the label only).
 */
class IqaAssessmentSchema
{
    public const TITLE = 'NATIONAL POLYTECHNIC QUALITY AUDIT TOOL';

    public const SECTION_COUNT = 8;

    /**
     * @return array<int, array{number: string, title: string, short: string}>
     */
    public static function sectionMeta(): array
    {
        return [
            1 => ['number' => '1.0', 'title' => 'LEADERSHIP, MANAGEMENT AND GOVERNANCE', 'short' => 'Leadership'],
            2 => ['number' => '2.0', 'title' => 'AUDIT CRITERIA FOR PHYSICAL RESOURCES', 'short' => 'Physical resources'],
            3 => ['number' => '3.0', 'title' => 'Audit Criteria for Human Resources', 'short' => 'Human resources'],
            4 => ['number' => '4.0', 'title' => 'AUDIT CRITERIA FOR TRAINING DELIVERY', 'short' => 'Training delivery'],
            5 => ['number' => '5.0', 'title' => 'PROGRAMMES EVALUATION (SCORE AS PER ANALYSIS IN 5.1 BELOW)', 'short' => 'Programmes'],
            6 => ['number' => '6.0', 'title' => 'AUDIT CRITERIA FOR TRAINEE SUPPORT', 'short' => 'Trainee support'],
            7 => ['number' => '7.0', 'title' => 'AUDIT CRITERIA FOR INNOVATION, RESEARCH AND COOPERATION', 'short' => 'Innovation'],
            8 => ['number' => '8.0', 'title' => 'QUALITY AUDITORS', 'short' => 'Auditors'],
        ];
    }

    /**
     * @return array{sections: array<string, mixed>, auditors: list<array{name: string, date: string, signature: string}>, walkthrough: list<int>}
     */
    public static function emptyPayload(): array
    {
        $sections = [];
        foreach (range(1, 7) as $n) {
            $def = self::sectionDefinition($n);
            $sections[(string) $n] = [
                'items' => array_map(static function (array $item): array {
                    return array_merge($item, [
                        'observations' => '',
                        'recommendations' => '',
                    ]);
                }, $def['items']),
                'tables' => self::emptyTables($n),
                'overall_recommendations' => '',
                'layout' => $def['layout'],
            ];
        }

        return [
            'sections' => $sections,
            'auditors' => [
                ['name' => '', 'date' => '', 'signature' => ''],
                ['name' => '', 'date' => '', 'signature' => ''],
                ['name' => '', 'date' => '', 'signature' => ''],
            ],
            'walkthrough' => [],
        ];
    }

    /**
     * @return array{items: list<array{key: string, area_code: string, audit_area: string, indicator: string}>, tables: list<string>, layout: string}
     */
    public static function sectionDefinition(int $section): array
    {
        return match ($section) {
            1 => ['items' => self::section1Items(), 'tables' => [], 'layout' => 'areas'],
            2 => ['items' => self::section2Items(), 'tables' => ['admin_offices', 'theory_rooms', 'workshops_labs', 'tools_equipment'], 'layout' => 'areas'],
            3 => ['items' => self::section3Items(), 'tables' => ['trainers_analysis'], 'layout' => 'areas'],
            4 => ['items' => self::section4Items(), 'tables' => [], 'layout' => 'areas'],
            5 => ['items' => self::section5Items(), 'tables' => ['programmes_data'], 'layout' => 'numbered'],
            6 => ['items' => self::section6Items(), 'tables' => [], 'layout' => 'areas'],
            7 => ['items' => self::section7Items(), 'tables' => [], 'layout' => 'areas'],
            default => ['items' => [], 'tables' => [], 'layout' => 'areas'],
        };
    }

    /**
     * @param  list<string>  $indicators
     * @return list<array{key: string, area_code: string, audit_area: string, indicator: string}>
     */
    private static function area(string $code, string $label, array $indicators): array
    {
        $items = [];
        foreach ($indicators as $i => $text) {
            $items[] = [
                'key' => $code.'.'.($i + 1),
                'area_code' => $code,
                'audit_area' => $label,
                'indicator' => $text,
            ];
        }

        return $items;
    }

    /** @return list<array{key: string, area_code: string, audit_area: string, indicator: string}> */
    private static function section1Items(): array
    {
        return array_merge(
            self::area('1.1', 'Strategic plan', [
                'Availability (minimum of 3 years)',
                'Vision and mission clearly stated.',
                'Clarity of objectives.',
                'Evidence of implementation.',
                'Budget projections',
                'Evidence of Organizational Structure',
            ]),
            self::area('1.2', 'Board of Governors/ Directors/ Council', [
                'Validity of the Council members',
                'Properly constituted (Complying with the TVET Act)',
                'Frequency of Meetings (Evidence of Minutes)',
                'Existence of The Board committees.',
                'Qualifications and experience of The Board members.',
            ]),
            self::area('1.3', 'Senior Management /Academic board', [
                'Gender Balance',
                'Qualified and Experienced',
                'Frequency of Meetings (Evidence of Minutes at least Once Termly)',
                'Evidence of implementation of management decisions.',
            ]),
            self::area('1.4', 'Internal quality assurance', [
                'Availability of a committee (appointment letters)',
                'Meetings (minutes on frequency)',
                'IQA Schedule of activities/work plan',
                'IQA Reports',
            ]),
            self::area('1.5', 'Administrative documents', [
                'Staff payroll inventories',
                'Remittance of Statutory deductions (Quality Assurance Fee, NHIF, NSSF, PAYE, Housing Levy)',
                'Visitors book',
                'Land ownership documents.eg. Title Deed, Lease Agreement, Allotment letter',
                'Student Management Records E.g. Admission Registe, Certificates/Transcripts/Result slip issuance register',
                'Valid public health report',
            ]),
            self::area('1.6', 'Legal documents', [
                'The Constitution of Kenya, 2010',
                'Sessional Paper No 1 of 2019',
                'TVET Act 2013',
                'Basic Education Act, 2012',
                'Kenya National Qualifications Framework Act 2014',
                'KNEC Act 2012',
                'Occupation Safety & Health Act (OSHA) 2007',
                'Work Injuries and Benefits Act (WIBA)2007',
                'Labour Relations Act of 2007',
                'Employment Act, 2007',
                'TVETA Standards and Guidelines (e.g. RPL, Trainer qualifications Framework)',
            ]),
            self::area('1.7', 'Financial Management', [
                'Fees Register',
                'Receipts Book/evidence of fee payments/collections',
                'Cashbook',
                'Approved Budget',
                'Procurement Plan',
                'Audited financial reports',
            ]),
        );
    }

    /** @return list<array{key: string, area_code: string, audit_area: string, indicator: string}> */
    private static function section2Items(): array
    {
        return array_merge(
            self::area('2.5', 'Safety measures', [
                'Firefighting equipment (Serviced)',
                'First aid kit',
                'Emergency exit (Signage)',
                'Fire assembly point',
                'Fire drills',
                'Personal protective equipment',
                'Fencing and gate',
                'Adherence to Health Guidelines',
            ]),
            self::area('2.6', 'Sanitation facilities', [
                'Availability of washrooms/urinals (Trainers, trainees)',
                'Adequacy (male, female, Sanitary bins)',
                'Condition (Cleanliness, Lighting)',
                'Waste disposal (solid and liquid)',
                'Provision for special needs',
            ]),
            self::area('2.7', 'Utilities', [
                'Power and backup system',
                'Renewable energy(availability)',
                'Water (adequate, safe and reliable)',
                'Sports facility (availability/MoU, adequacy)',
                'Lawns (maintenance)',
                'Proper signages for direction',
            ]),
            self::area('2.8', 'Library services', [
                'Availability',
                'Borrowing system in place and utilized',
                'Reading space versus enrollment (space should accommodate 10% of the student population)',
                'Adequacy of reference materials (relevant to programmes offered)',
                'Qualified library staff',
                'Provision of E-resource',
            ]),
        );
    }

    /** @return list<array{key: string, area_code: string, audit_area: string, indicator: string}> */
    private static function section3Items(): array
    {
        return array_merge(
            self::area('3.1', 'Trainers', [
                'Maintenance of trainers\' qualifications records',
                'Trainers\' Bio data (availability of records)',
                'Development plan (Capacity building)',
                'Trainers Workload (Sufficient workload max 28 hours)',
                'Availability of staff establishment (Documented)',
                'HR Policies/Procedure Implemented in place (Gender, Disability)',
                'Availability of job descriptions',
                'Existence of performance management system (Appraisals)',
            ]),
            self::area('3.2', 'Support Staff', [
                'Sufficient for institutional functions',
                'Appropriately deployed (Qualifications and experience)',
                'Scheme of service/Career progression guideline',
                'Balanced (Gender)',
                'HR Policies/Procedure Implemented in place (Gender, Disability)',
                'Availability of job descriptions',
                'Existence of performance management system (Appraisals)',
                'Capacity development plans',
                'Compliance certificates (Good conduct for watchmen)',
                'Compliance certificate (public health certificate for food handlers)',
            ]),
        );
    }

    /** @return list<array{key: string, area_code: string, audit_area: string, indicator: string}> */
    private static function section4Items(): array
    {
        return array_merge(
            self::area('4.1', 'Timetable', [
                'Availability',
                'Trainee friendly',
                'Reflects curriculum requirements',
            ]),
            self::area('4.2', 'Classrooms Attendance', [
                'Lessons are attended as timetabled',
                'Appropriate training methodology used',
                'Mechanism for training supervision (Trainee Attendance marked, Trainer Attendance marked)',
            ]),
            self::area('4.3', 'Updated professional documents', [
                'Occupational Standards (OS)',
                'Curriculum (Indicate the Cycle)',
                'Session Plan',
                'Learning Plan',
                'Mentoring Tools',
                'Record of work',
                'Portfolio of Evidence',
                'Learning Guides',
                'Training notes/handouts',
            ]),
            self::area('4.4', 'Assessment/ Examinations', [
                'Assessment is regular',
                'Mechanism for setting and validation in place',
                'Analysis of assessment',
                'Developing a schedule for verification activities',
                'Examining assessment tools',
                'Conducting meetings with the assessors to review assessment tools',
                'Developing a sampling plan and selecting a representative sample',
                'Observing assessors conducting assessment & giving feedback',
                'Examining the assessment documents for selected candidates',
                'Confirming the authenticity of the candidates\' evidence',
                'Conducting assessment of the selected candidates & provide feedback',
                'Supervision of Assessment',
                'Candidates\' Tool',
                'Assessors\' Tool',
                'Assessment policy',
                'Verification mechanism',
            ]),
            self::area('4.5', 'Industrial attachments as part of the learning process', [
                'Industrial attachment policy available',
                'Industrial attachment carried out',
                'Sourcing for placement of trainees',
                'Records maintained (Logbooks, Placement list)',
                'Industrial Liaison Officer (ILO) in place',
                'Trainees supervised (Supervision schedules)',
                'Insurance for trainees',
            ]),
        );
    }

    /** @return list<array{key: string, area_code: string, audit_area: string, indicator: string}> */
    private static function section5Items(): array
    {
        $indicators = [
            '1' => 'Accredited (licensed)',
            '2' => 'Enrollment (within the approved ceiling)',
            '3' => 'Programme versus Trainers',
            '4' => 'Programme versus Facilities',
            '5' => 'Trainer-trainee ratio per course is within provided standards guidelines',
            '6' => '*Number of Programmes under collaboration with universities (for NPs)',
            '7' => '*Technical versus Business (proportion of each)',
            '8' => 'Trainees\' Gender Balance (Proportion)',
            '9' => 'Level of programmes (proportion of Artisans, Craft, Diploma)',
            '10' => 'Trainers Qualification (proportion of qualified trainers)',
            '11' => 'Trainers Registration status (Proportion of Registered Trainers)',
        ];

        $items = [];
        foreach ($indicators as $num => $text) {
            $items[] = [
                'key' => '5.'.$num,
                'area_code' => $num,
                'audit_area' => '',
                'indicator' => $text,
            ];
        }

        return $items;
    }

    /** @return list<array{key: string, area_code: string, audit_area: string, indicator: string}> */
    private static function section6Items(): array
    {
        return array_merge(
            self::area('6.1', 'Trainee support procedures', [
                'Admission procedures (Trainee admission register)',
                'Code of conduct (Evidence of use, trainee awareness)',
                'Discipline procedure (Developed and implemented)',
                'Complaints handling procedure (Documented and implemented)',
            ]),
            self::area('6.2', 'Trainee welfare', [
                'Staff in charge',
                'Scholarships (Beneficiaries documented, Trainee awareness, MoU)',
                'Trainee representative (Available, mode of appointment, democratic space)',
                'Clubs and sports',
                'Vulnerable trainee support e.g. expectant mothers, orphans, PWDs (Documentation, support programme)',
                'Availability of Guidance and Counseling unit',
                'Appointment of head of G&C',
                'Evidence of Guidance and Counseling records',
                'Career Guidance Services/orientation (Schedules, staff in charge)',
            ]),
            self::area('6.3', 'Alumni Networks', [
                'Availability/ Database/ Documentation',
                'Active (activities in institution, meetings',
            ]),
            self::area('6.4', 'Accommodation (where applicable)', [
                'Availability',
                'Availability of a qualified matron/janitor',
                'Condition (State of maintenance e.g. painting, tidiness, Lighting, ventilation)',
                'Adequacy (standard size)',
                'Organization (well arranged)',
                'Safety provisions (instructions/rules, fire, doors opening)',
                'Accessibility (Ramps, lifts)',
            ]),
        );
    }

    /** @return list<array{key: string, area_code: string, audit_area: string, indicator: string}> */
    private static function section7Items(): array
    {
        return array_merge(
            self::area('7.1', 'Innovation', [
                'Innovation Committee in place',
                'Initiated innovation',
                'Innovations patenting',
                'Promotion of innovations',
                'Commercialization',
                'Open days organized',
            ]),
            self::area('7.2', 'Labour market/industry information', [
                'Mechanism for obtaining feedback',
                'Documentation of information',
                'Use of feedback to improve training (minutes of meetings to discuss the same, course outline etc.)',
            ]),
            self::area('7.3', 'Research Initiative', [
                'Research unit established/committee',
                'Activities planned',
                'Support system/budget',
                'Partnering with other organizations in research',
                'System for disseminating research findings e.g. journals, conferences, symposium,',
                'Customer satisfaction and employee satisfaction survey',
            ]),
            self::area('7.4', 'Linkages and collaborations', [
                'Activities planned',
                'Participation in benchmarking',
                'Participation in Skills shows/ competitions/trade fairs',
                'Industrial /Field visits',
                'Partnerships (MoUs)',
            ]),
            self::area('7.5', 'Participating in community activities/ Corporate social responsibility', [
                'Community needs identified',
                'Activities and plans to respond to community needs',
                'Greening/ Resource conservation (tree planting, water harvesting, bio gas, renewable energy)',
            ]),
            self::area('7.6', 'Entrepreneurship and income generating activities', [
                'Production units initiated',
                'Trainees participating in production units',
                'Enterpreneurship mentoring',
                'Marketing Strategies e.g. Exhibitions/ trade fairs etc.',
            ]),
        );
    }

    /**
     * @return array<string, array{columns: list<string>, rows: list<array<string, string>>, remarks: string, fixed_labels?: list<string|null>, title?: string, note?: string}>
     */
    private static function emptyTables(int $section): array
    {
        return match ($section) {
            2 => [
                'admin_offices' => array_merge(
                    self::fixedLabelTable(
                        ['offices', 'size_m', 'condition', 'fit_for_purpose', 'accessibility_pwds', 'furniture'],
                        ["Principal's Office", 'Staff room', 'G&C room']
                    ),
                    ['title' => '2.1 Administrative offices', 'note' => '']
                ),
                'theory_rooms' => array_merge(
                    self::blankRowsTable(
                        ['lecture_rooms', 'size', 'capacity_per_shift', 'accessibility_pwds', 'condition', 'furniture', 'fit_for_programme', 'adequacy'],
                        10
                    ),
                    ['title' => '2.2 Theory rooms- Sample a maximum of 10 lecture rooms, preferably from different departments (score from accessibility to adequacy)', 'note' => '']
                ),
                'workshops_labs' => array_merge(
                    self::blankRowsTable(
                        ['workshop_lab', 'size', 'capacity', 'accessibility', 'condition', 'fit', 'adequacy', 'safety', 'workshop_layout'],
                        10
                    ),
                    [
                        'title' => '2.3 Workshops /Laboratories Sample a maximum of 10 workshop, preferably from different department (score from accessibility to organization)',
                        'note' => '*Workshop layout; adequate spacing, marked floor, clear gangways, machine, and equipment arrangements, properly labelled signages',
                    ]
                ),
                'tools_equipment' => array_merge(
                    self::blankRowsTable(
                        ['sampled_workshop', 'relevant', 'condition_functionality', 'technician', 'inventory', 'adequacy', 'organization_storage', 'installed_labelled'],
                        10
                    ),
                    ['title' => '2.4 Tools and Equipment (as per the workshops sampled above preferably from 10 different department)', 'note' => '']
                ),
            ],
            3 => [
                'trainers_analysis' => array_merge(
                    self::blankRowsTable(
                        ['name', 'qualifications', 'experience', 'training_area', 'tveta_license_no', 'score', 'remarks'],
                        10
                    ),
                    ['title' => '3.3 Trainers Analysis Template', 'note' => '']
                ),
            ],
            5 => [
                'programmes_data' => array_merge(
                    self::blankRowsTable(
                        ['courses', 'level', 'exam_body', 'approved_enrolment', 'trainers_availability', 'adequate_facilities', 'licensed_tveta_score', 'enrolment_m', 'enrolment_f', 'enrolment_total', 'remarks'],
                        10
                    ),
                    ['title' => '5.1 Programmes data Sheet (populate as per the template)', 'note' => '']
                ),
            ],
            default => [],
        };
    }

    /**
     * @param  list<string>  $columns
     * @param  list<string>  $labels
     * @return array{columns: list<string>, rows: list<array<string, string>>, remarks: string, fixed_labels: list<string>}
     */
    private static function fixedLabelTable(array $columns, array $labels): array
    {
        $rows = [];
        foreach ($labels as $label) {
            $row = [];
            foreach ($columns as $i => $col) {
                $row[$col] = $i === 0 ? $label : '';
            }
            $rows[] = $row;
        }

        return [
            'columns' => $columns,
            'rows' => $rows,
            'remarks' => '',
            'fixed_labels' => $labels,
        ];
    }

    /**
     * @param  list<string>  $columns
     * @return array{columns: list<string>, rows: list<array<string, string>>, remarks: string}
     */
    private static function blankRowsTable(array $columns, int $count): array
    {
        $rows = [];
        for ($i = 0; $i < $count; $i++) {
            $row = [];
            foreach ($columns as $col) {
                $row[$col] = '';
            }
            $rows[] = $row;
        }

        return [
            'columns' => $columns,
            'rows' => $rows,
            'remarks' => '',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function tableColumnLabels(string $tableKey): array
    {
        return match ($tableKey) {
            'admin_offices' => [
                'offices' => 'Offices',
                'size_m' => 'Size (m)',
                'condition' => 'Condition',
                'fit_for_purpose' => 'Fit for Purpose',
                'accessibility_pwds' => 'Accessibility (PWDs)',
                'furniture' => 'Availability of suitable Furniture',
            ],
            'theory_rooms' => [
                'lecture_rooms' => 'Lecture Rooms',
                'size' => 'Size (m)',
                'capacity_per_shift' => 'Capacity per shift',
                'accessibility_pwds' => 'Accessibility PWDs',
                'condition' => 'Condition',
                'furniture' => 'Availability of suitable Furniture',
                'fit_for_programme' => 'Fit for the programme',
                'adequacy' => 'Adequacy',
            ],
            'workshops_labs' => [
                'workshop_lab' => 'Workshops',
                'size' => 'Size (m)',
                'capacity' => 'Capacity per shift',
                'accessibility' => 'Accessibility PWDs',
                'condition' => 'Condition',
                'fit' => 'Fit for the programme',
                'adequacy' => 'Adequacy',
                'safety' => 'Safety',
                'workshop_layout' => 'Workshop Layout',
            ],
            'tools_equipment' => [
                'sampled_workshop' => 'Sampled Workshop (e.g MVM, Carpentry etc)',
                'relevant' => 'Relevant for the Programme',
                'condition_functionality' => 'Condition and Funtionality',
                'technician' => 'Availability of Technician',
                'inventory' => 'Inventory',
                'adequacy' => 'Adequacy',
                'organization_storage' => 'Organization/Storage (Arrangement of Tools/ Equipment)',
                'installed_labelled' => 'Properly Installed and Labelled',
            ],
            'trainers_analysis' => [
                'name' => 'Trainers Name',
                'qualifications' => 'Qualifications (Specify area of specialization)',
                'experience' => 'Experience (Training & Industry experience)',
                'training_area' => 'Training area',
                'tveta_license_no' => 'TVETA License No.',
                'score' => 'Score (Accreditation status)',
                'remarks' => 'Remarks',
            ],
            'programmes_data' => [
                'courses' => 'Courses',
                'level' => 'Level',
                'exam_body' => 'Exam Body',
                'approved_enrolment' => 'Approved Enrolment (as per TVETA License)',
                'trainers_availability' => 'Trainers Availability',
                'adequate_facilities' => 'Adequate Facilities',
                'licensed_tveta_score' => 'Licensed by TVETA (score)',
                'enrolment_m' => 'Actual Enrolment M',
                'enrolment_f' => 'Actual Enrolment F',
                'enrolment_total' => 'Actual Enrolment Total',
                'remarks' => 'Remarks',
            ],
            default => [],
        };
    }

    /**
     * @return array<string, string>
     */
    public static function tableTitles(): array
    {
        return [
            'admin_offices' => '2.1 Administrative offices',
            'theory_rooms' => '2.2 Theory rooms- Sample a maximum of 10 lecture rooms, preferably from different departments (score from accessibility to adequacy)',
            'workshops_labs' => '2.3 Workshops /Laboratories Sample a maximum of 10 workshop, preferably from different department (score from accessibility to organization)',
            'tools_equipment' => '2.4 Tools and Equipment (as per the workshops sampled above preferably from 10 different department)',
            'trainers_analysis' => '3.3 Trainers Analysis Template',
            'programmes_data' => '5.1 Programmes data Sheet (populate as per the template)',
        ];
    }

    /**
     * Group checklist items by area for rowspan rendering.
     *
     * @param  list<array<string, mixed>>  $items
     * @return list<array{area_code: string, audit_area: string, rows: list<array<string, mixed>>}>
     */
    public static function groupItemsByArea(array $items): array
    {
        $groups = [];
        $order = [];
        foreach ($items as $item) {
            $code = (string) ($item['area_code'] ?? '');
            if (! isset($groups[$code])) {
                $groups[$code] = [
                    'area_code' => $code,
                    'audit_area' => (string) ($item['audit_area'] ?? ''),
                    'rows' => [],
                ];
                $order[] = $code;
            }
            $groups[$code]['rows'][] = $item;
        }

        return array_map(static fn (string $code) => $groups[$code], $order);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public static function mergeSectionInput(array $payload, int $section, array $input): array
    {
        if ($section === 8) {
            return $payload;
        }

        $key = (string) $section;
        $sectionData = $payload['sections'][$key] ?? null;
        if (! is_array($sectionData)) {
            return $payload;
        }

        $def = self::sectionDefinition($section);
        $sectionData['layout'] = $def['layout'];

        $itemsInput = is_array($input['items'] ?? null) ? $input['items'] : [];
        $seenArea = [];
        foreach ($sectionData['items'] as $i => $item) {
            $areaKey = (string) ($item['area_code'] ?? $i);
            $isFirst = ! isset($seenArea[$areaKey]);
            $seenArea[$areaKey] = true;

            $row = $itemsInput[$i] ?? [];
            if (! is_array($row)) {
                $row = [];
            }

            if ($isFirst) {
                $sectionData['items'][$i]['observations'] = (string) ($row['observations'] ?? $item['observations'] ?? '');
                $sectionData['items'][$i]['recommendations'] = (string) ($row['recommendations'] ?? $item['recommendations'] ?? '');
            } else {
                $sectionData['items'][$i]['observations'] = '';
                $sectionData['items'][$i]['recommendations'] = '';
            }
            unset($sectionData['items'][$i]['target']);
        }

        $tablesInput = is_array($input['tables'] ?? null) ? $input['tables'] : [];
        foreach ($sectionData['tables'] as $tableKey => $table) {
            $posted = $tablesInput[$tableKey] ?? null;
            if (! is_array($posted)) {
                continue;
            }
            $sectionData['tables'][$tableKey]['remarks'] = (string) ($posted['remarks'] ?? $table['remarks'] ?? '');
            $rows = is_array($posted['rows'] ?? null) ? $posted['rows'] : [];
            foreach ($table['rows'] as $ri => $row) {
                $postedRow = $rows[$ri] ?? [];
                if (! is_array($postedRow)) {
                    continue;
                }
                $fixed = $table['fixed_labels'][$ri] ?? null;
                foreach (array_keys($row) as $col) {
                    if ($fixed !== null && $col === ($table['columns'][0] ?? null)) {
                        $sectionData['tables'][$tableKey]['rows'][$ri][$col] = $fixed;
                        continue;
                    }
                    $sectionData['tables'][$tableKey]['rows'][$ri][$col] = (string) ($postedRow[$col] ?? '');
                }
            }
        }

        $sectionData['overall_recommendations'] = (string) ($input['overall_recommendations'] ?? '');
        $payload['sections'][$key] = $sectionData;

        return $payload;
    }

    /**
     * Re-shape a stored payload to the current schema (drops legacy target; remaps by key when possible).
     *
     * @param  array<string, mixed>|null  $payload
     * @return array<string, mixed>
     */
    public static function normalizePayload(?array $payload): array
    {
        $fresh = self::emptyPayload();
        if (! is_array($payload)) {
            return $fresh;
        }

        foreach (range(1, 7) as $n) {
            $key = (string) $n;
            $stored = $payload['sections'][$key] ?? null;
            if (! is_array($stored)) {
                continue;
            }

            $byKey = [];
            foreach (($stored['items'] ?? []) as $item) {
                if (is_array($item) && isset($item['key'])) {
                    $byKey[(string) $item['key']] = $item;
                }
            }

            $seenArea = [];
            foreach ($fresh['sections'][$key]['items'] as $i => $item) {
                $match = $byKey[$item['key']] ?? null;
                if (! is_array($match)) {
                    foreach (($stored['items'] ?? []) as $old) {
                        if (! is_array($old)) {
                            continue;
                        }
                        if (($old['indicator'] ?? '') === $item['indicator']
                            || (str_contains((string) ($old['audit_area'] ?? ''), $item['area_code'])
                                && ($old['sno'] ?? '') === (string) ($i + 1))) {
                            $match = $old;
                            break;
                        }
                    }
                }

                $areaKey = (string) ($item['area_code'] ?? $i);
                $isFirst = ! isset($seenArea[$areaKey]);
                $seenArea[$areaKey] = true;

                if ($isFirst && is_array($match)) {
                    $fresh['sections'][$key]['items'][$i]['observations'] = (string) ($match['observations'] ?? '');
                    $fresh['sections'][$key]['items'][$i]['recommendations'] = (string) ($match['recommendations'] ?? '');
                } else {
                    $fresh['sections'][$key]['items'][$i]['observations'] = '';
                    $fresh['sections'][$key]['items'][$i]['recommendations'] = '';
                }
            }

            foreach ($fresh['sections'][$key]['tables'] as $tableKey => $table) {
                $oldTable = $stored['tables'][$tableKey] ?? null;
                if (! is_array($oldTable)) {
                    continue;
                }
                $fresh['sections'][$key]['tables'][$tableKey]['remarks'] = (string) ($oldTable['remarks'] ?? '');
                foreach ($table['rows'] as $ri => $row) {
                    $oldRow = $oldTable['rows'][$ri] ?? null;
                    if (! is_array($oldRow)) {
                        continue;
                    }
                    foreach (array_keys($row) as $col) {
                        if (isset($table['fixed_labels'][$ri]) && $col === ($table['columns'][0] ?? null)) {
                            continue;
                        }
                        $fresh['sections'][$key]['tables'][$tableKey]['rows'][$ri][$col] = (string) ($oldRow[$col] ?? '');
                    }
                }
            }

            $fresh['sections'][$key]['overall_recommendations'] = (string) ($stored['overall_recommendations'] ?? '');
        }

        if (isset($payload['auditors']) && is_array($payload['auditors'])) {
            $fresh['auditors'] = array_values(array_map(static function ($row) {
                return [
                    'name' => (string) ($row['name'] ?? ''),
                    'date' => (string) ($row['date'] ?? ''),
                    'signature' => (string) ($row['signature'] ?? ''),
                ];
            }, $payload['auditors']));
            while (count($fresh['auditors']) < 3) {
                $fresh['auditors'][] = ['name' => '', 'date' => '', 'signature' => ''];
            }
        }

        $fresh['walkthrough'] = array_values(array_map('intval', $payload['walkthrough'] ?? []));

        return $fresh;
    }
}
