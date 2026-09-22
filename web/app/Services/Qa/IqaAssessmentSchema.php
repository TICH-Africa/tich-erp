<?php

namespace App\Services\Qa;

/**
 * Hardcoded NATIONAL POLYTECHNIC QUALITY AUDIT TOOL structure (sections 1.0–7.0 + auditors).
 * Target / Observations / Recommendations / scores remain blank for the QA Officer to fill.
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
            2 => ['number' => '2.0', 'title' => 'PHYSICAL RESOURCES', 'short' => 'Physical resources'],
            3 => ['number' => '3.0', 'title' => 'HUMAN RESOURCES', 'short' => 'Human resources'],
            4 => ['number' => '4.0', 'title' => 'TRAINING DELIVERY', 'short' => 'Training delivery'],
            5 => ['number' => '5.0', 'title' => 'PROGRAMMES EVALUATION', 'short' => 'Programmes'],
            6 => ['number' => '6.0', 'title' => 'TRAINEE SUPPORT', 'short' => 'Trainee support'],
            7 => ['number' => '7.0', 'title' => 'INNOVATION, RESEARCH AND COOPERATION', 'short' => 'Innovation'],
            8 => ['number' => '8.0', 'title' => 'QUALITY AUDITORS', 'short' => 'Auditors'],
        ];
    }

    /**
     * Empty answer payload shaped to the PDF structure.
     *
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
                        'target' => '',
                        'observations' => '',
                        'recommendations' => '',
                    ]);
                }, $def['items']),
                'tables' => self::emptyTables($n),
                'overall_recommendations' => '',
            ];
        }

        return [
            'sections' => $sections,
            'auditors' => [
                ['name' => '', 'date' => '', 'signature' => ''],
            ],
            'walkthrough' => [],
        ];
    }

    /**
     * @return array{items: list<array{key: string, sno: string, audit_area: string, indicator: string}>, tables: list<string>}
     */
    public static function sectionDefinition(int $section): array
    {
        return match ($section) {
            1 => self::section1(),
            2 => self::section2(),
            3 => self::section3(),
            4 => self::section4(),
            5 => self::section5(),
            6 => self::section6(),
            7 => self::section7(),
            default => ['items' => [], 'tables' => []],
        };
    }

    /**
     * @return array<string, array{columns: list<string>, rows: list<array<string, string>>, remarks: string, fixed_labels?: list<string|null>}>
     */
    private static function emptyTables(int $section): array
    {
        return match ($section) {
            2 => [
                'admin_offices' => self::fixedLabelTable(
                    ['offices', 'size_m', 'condition', 'fit_for_purpose', 'accessibility_pwds', 'furniture'],
                    ["Principal's Office", 'Staff room', 'G&C room']
                ),
                'theory_rooms' => self::blankRowsTable(
                    ['lecture_rooms', 'size', 'capacity_per_shift', 'accessibility_pwds', 'condition', 'furniture', 'fit_for_programme', 'adequacy'],
                    10
                ),
                'workshops_labs' => self::blankRowsTable(
                    ['workshop_lab', 'size', 'capacity', 'accessibility', 'condition', 'fit', 'adequacy', 'safety', 'workshop_layout'],
                    10
                ),
                'tools_equipment' => self::blankRowsTable(
                    ['sampled_workshop', 'relevant', 'condition_functionality', 'technician', 'inventory', 'adequacy', 'organization_storage', 'installed_labelled'],
                    10
                ),
            ],
            3 => [
                'trainers_analysis' => self::blankRowsTable(
                    ['name', 'qualifications', 'experience', 'training_area', 'tveta_license_no', 'score', 'remarks'],
                    10
                ),
            ],
            5 => [
                'programmes_data' => self::blankRowsTable(
                    ['courses', 'level', 'exam_body', 'approved_enrolment', 'trainers_availability', 'adequate_facilities', 'licensed_tveta_score', 'enrolment_m', 'enrolment_f', 'enrolment_total', 'remarks'],
                    10
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
     * @param  list<array{0: string, 1: string}>  $indicators  [sno, text]
     * @return list<array{key: string, sno: string, audit_area: string, indicator: string}>
     */
    private static function areaItems(string $areaKey, string $areaLabel, array $indicators): array
    {
        $items = [];
        foreach ($indicators as [$sno, $text]) {
            $items[] = [
                'key' => $areaKey.'.'.$sno,
                'sno' => $sno,
                'audit_area' => $areaLabel,
                'indicator' => $text,
            ];
        }

        return $items;
    }

    /** @return array{items: list<array{key: string, sno: string, audit_area: string, indicator: string}>, tables: list<string>} */
    private static function section1(): array
    {
        $items = array_merge(
            self::areaItems('1.1', '1.1 Strategic plan', [
                ['1', 'Availability of a current Strategic Plan covering the audit period'],
                ['2', 'Strategic Plan developed through a participatory / stakeholder process'],
                ['3', 'Strategic Plan aligned to national TVET policies, TVETA standards and Vision 2030'],
                ['4', 'Clear Vision, Mission and Core Values stated and displayed'],
                ['5', 'Strategic objectives are SMART and measurable'],
                ['6', 'Operational / annual work plans cascaded from the Strategic Plan'],
                ['7', 'Regular monitoring and evaluation of Strategic Plan implementation'],
                ['8', 'Strategic Plan reviewed and updated periodically'],
            ]),
            self::areaItems('1.2', '1.2 Board of Governors/Directors/Council', [
                ['1', 'Existence of a functional Board of Governors / Directors / Council'],
                ['2', 'Board composition complies with the applicable legal / TVETA requirements'],
                ['3', 'Regular Board meetings held as per the approved schedule'],
                ['4', 'Board minutes properly recorded, signed and filed'],
                ['5', 'Board provides strategic oversight and policy direction'],
                ['6', 'Conflict of interest policy exists and is applied for Board members'],
                ['7', 'Board induction and capacity-building programmes conducted'],
            ]),
            self::areaItems('1.3', '1.3 Senior Management/Academic board', [
                ['1', 'Existence of a functional Senior Management Team'],
                ['2', 'Regular management meetings held with documented minutes'],
                ['3', 'Academic Board / Academic Committee exists and functions'],
                ['4', 'Clear organogram with defined reporting lines'],
                ['5', 'Delegation of authority documented and applied'],
                ['6', 'Management decisions implemented and followed up'],
            ]),
            self::areaItems('1.4', '1.4 Internal quality assurance', [
                ['1', 'Internal Quality Assurance (IQA) policy and procedures in place'],
                ['2', 'Designated IQA officer / quality unit appointed'],
                ['3', 'Regular internal quality audits conducted'],
                ['4', 'Quality improvement / corrective action plans implemented'],
                ['5', 'Stakeholder feedback mechanisms established and used'],
                ['6', 'IQA reports presented to management and the Board'],
            ]),
            self::areaItems('1.5', '1.5 Administrative documents', [
                ['1', 'Institutional policies and procedures manuals available and current'],
                ['2', 'Document control / version-control system in place'],
                ['3', 'Records management policy implemented'],
                ['4', 'Filing, storage and retrieval systems functional'],
                ['5', 'Key administrative registers maintained (admission, staff, assets, etc.)'],
            ]),
            self::areaItems('1.6', '1.6 Legal documents', [
                ['1', 'Valid institutional registration / incorporation documents'],
                ['2', 'Valid TVETA registration / accreditation certificates'],
                ['3', 'Title deeds, leases or occupation agreements for premises'],
                ['4', 'Statutory compliance certificates (fire, health, NEMA, etc.) current'],
                ['5', 'Insurance covers for property, liability and trainees where applicable'],
            ]),
            self::areaItems('1.7', '1.7 Financial Management', [
                ['1', 'Approved annual budget aligned to the Strategic Plan'],
                ['2', 'Financial policies and procedures documented and applied'],
                ['3', 'Regular financial reports prepared and reviewed by management / Board'],
                ['4', 'External / statutory audit conducted within the required period'],
                ['5', 'Approved fee structure and transparent fee collection processes'],
                ['6', 'Asset register maintained and updated'],
                ['7', 'Internal financial controls and segregation of duties in place'],
            ]),
        );

        return ['items' => $items, 'tables' => []];
    }

    /** @return array{items: list<array{key: string, sno: string, audit_area: string, indicator: string}>, tables: list<string>} */
    private static function section2(): array
    {
        $items = array_merge(
            self::areaItems('2.5', '2.5 Safety measures', [
                ['1', 'Institutional safety policy and procedures in place'],
                ['2', 'Fire extinguishers, hose reels and assembly points available and serviced'],
                ['3', 'First-aid kits and trained first-aiders available'],
                ['4', 'Emergency exits clearly marked and unobstructed'],
                ['5', 'Workshop / laboratory safety rules displayed and enforced'],
                ['6', 'Personal protective equipment (PPE) available and used where required'],
            ]),
            self::areaItems('2.6', '2.6 Sanitation', [
                ['1', 'Adequate toilets for male and female trainees and staff'],
                ['2', 'PWD-accessible sanitation facilities available'],
                ['3', 'Sanitation facilities clean, well-maintained and supplied'],
                ['4', 'Hand-washing facilities with soap and water available'],
                ['5', 'Waste disposal and drainage systems functional'],
            ]),
            self::areaItems('2.7', '2.7 Utilities', [
                ['1', 'Reliable electricity supply (main and/or backup)'],
                ['2', 'Adequate clean water supply for training and domestic use'],
                ['3', 'Internet / ICT connectivity adequate for teaching and administration'],
                ['4', 'Lighting and ventilation adequate in teaching spaces'],
                ['5', 'Utility bills paid and utility infrastructure maintained'],
            ]),
            self::areaItems('2.8', '2.8 Library', [
                ['1', 'Functional library / resource centre available'],
                ['2', 'Adequate relevant print and/or digital learning resources'],
                ['3', 'Library cataloguing, borrowing and stock-control systems in place'],
                ['4', 'Reading space adequate and conducive for learning'],
                ['5', 'Library staffed and accessible during training hours'],
                ['6', 'Library resources aligned to programmes offered'],
            ]),
        );

        return [
            'items' => $items,
            'tables' => ['admin_offices', 'theory_rooms', 'workshops_labs', 'tools_equipment'],
        ];
    }

    /** @return array{items: list<array{key: string, sno: string, audit_area: string, indicator: string}>, tables: list<string>} */
    private static function section3(): array
    {
        $items = array_merge(
            self::areaItems('3.1', '3.1 Trainers', [
                ['1', 'Adequate number of trainers relative to approved enrolment'],
                ['2', 'Trainers hold relevant academic and professional qualifications'],
                ['3', 'Trainers hold valid TVETA licences / registration'],
                ['4', 'Trainers have relevant industrial / professional experience'],
                ['5', 'Trainer-to-trainee ratios meet programme requirements'],
                ['6', 'Continuous professional development (CPD) for trainers undertaken'],
                ['7', 'Trainer performance appraisal conducted regularly'],
            ]),
            self::areaItems('3.2', '3.2 Support Staff', [
                ['1', 'Adequate support staff for administration and workshops'],
                ['2', 'Support staff have relevant qualifications for their roles'],
                ['3', 'Job descriptions and schemes of service available'],
                ['4', 'Support staff appraisal and development processes in place'],
                ['5', 'Technicians available for workshops / laboratories'],
            ]),
        );

        return ['items' => $items, 'tables' => ['trainers_analysis']];
    }

    /** @return array{items: list<array{key: string, sno: string, audit_area: string, indicator: string}>, tables: list<string>} */
    private static function section4(): array
    {
        $items = array_merge(
            self::areaItems('4.1', '4.1 Timetable', [
                ['1', 'Master and class timetables prepared and approved'],
                ['2', 'Timetable adequately covers theory and practical sessions'],
                ['3', 'Timetable communicated to trainers and trainees'],
                ['4', 'Timetable adhered to with minimal disruptions'],
            ]),
            self::areaItems('4.2', '4.2 Classrooms Attendance', [
                ['1', 'Class attendance registers maintained for all sessions'],
                ['2', 'Trainer attendance monitored and recorded'],
                ['3', 'Absenteeism followed up and remedial action taken'],
                ['4', 'Attendance records accurate and up to date'],
            ]),
            self::areaItems('4.3', '4.3 Updated professional documents', [
                ['1', 'Schemes of work prepared and approved for units offered'],
                ['2', 'Lesson plans / session plans available and current'],
                ['3', 'Training notes / learning materials updated'],
                ['4', 'Professional documents aligned to curriculum and occupational standards'],
            ]),
            self::areaItems('4.4', '4.4 Assessment/Examinations', [
                ['1', 'Assessment policy and procedures documented'],
                ['2', 'Continuous assessment conducted as per curriculum requirements'],
                ['3', 'Examination / assessment papers moderated'],
                ['4', 'Secure handling of examination materials'],
                ['5', 'Timely marking, feedback and results release'],
                ['6', 'External examination body requirements complied with'],
            ]),
            self::areaItems('4.5', '4.5 Industrial attachments', [
                ['1', 'Industrial attachment / internship policy in place'],
                ['2', 'Trainees placed for industrial attachment as required'],
                ['3', 'Attachment logbooks / reports supervised and assessed'],
                ['4', 'Industry linkages supporting attachment placements'],
                ['5', 'Feedback from industry partners used for improvement'],
            ]),
        );

        return ['items' => $items, 'tables' => []];
    }

    /** @return array{items: list<array{key: string, sno: string, audit_area: string, indicator: string}>, tables: list<string>} */
    private static function section5(): array
    {
        $items = self::areaItems('5.0', '5.0 Programmes evaluation', [
            ['1', 'Programmes offered are approved / licensed by TVETA'],
            ['2', 'Curricula aligned to occupational standards / examining body requirements'],
            ['3', 'Programme objectives clearly defined and communicated'],
            ['4', 'Adequate trainers available for each programme'],
            ['5', 'Adequate workshops, labs and equipment for each programme'],
            ['6', 'Enrolment within approved capacity'],
            ['7', 'Programme review and continuous improvement undertaken'],
            ['8', 'Industry / employer input into programme design and review'],
            ['9', 'Graduate tracer / employment outcomes monitored'],
            ['10', 'Programme marketing and admission criteria transparent'],
            ['11', 'Records of programme approval, review and accreditation maintained'],
        ]);

        return ['items' => $items, 'tables' => ['programmes_data']];
    }

    /** @return array{items: list<array{key: string, sno: string, audit_area: string, indicator: string}>, tables: list<string>} */
    private static function section6(): array
    {
        $items = array_merge(
            self::areaItems('6.1', '6.1 Guidance and counselling', [
                ['1', 'Functional guidance and counselling unit / services'],
                ['2', 'Qualified counsellor(s) available to trainees'],
                ['3', 'Confidential counselling records and referral pathways'],
                ['4', 'Awareness programmes on academic and personal support'],
            ]),
            self::areaItems('6.2', '6.2 Health services', [
                ['1', 'Health / sick-bay facilities or referral arrangements available'],
                ['2', 'First-aid and emergency response procedures known to trainees'],
                ['3', 'Health education / wellness programmes conducted'],
            ]),
            self::areaItems('6.3', '6.3 Co-curricular and recreation', [
                ['1', 'Co-curricular, sports and recreational activities available'],
                ['2', 'Adequate facilities / equipment for co-curricular activities'],
                ['3', 'Trainee clubs / societies supported and supervised'],
            ]),
            self::areaItems('6.4', '6.4 Welfare and inclusivity', [
                ['1', 'Trainee welfare policies (fees support, hardship, etc.) in place'],
                ['2', 'Support for trainees with disabilities (PWD)'],
                ['3', 'Gender equity and anti-harassment measures implemented'],
                ['4', 'Trainee representation / student council functional'],
                ['5', 'Grievance and complaints handling procedures for trainees'],
            ]),
        );

        return ['items' => $items, 'tables' => []];
    }

    /** @return array{items: list<array{key: string, sno: string, audit_area: string, indicator: string}>, tables: list<string>} */
    private static function section7(): array
    {
        $items = array_merge(
            self::areaItems('7.1', '7.1 Innovation', [
                ['1', 'Innovation / creativity policy or framework in place'],
                ['2', 'Innovation projects / prototypes by trainees and staff'],
                ['3', 'Support for incubation / commercialization of innovations'],
            ]),
            self::areaItems('7.2', '7.2 Research', [
                ['1', 'Research policy and ethical guidelines available'],
                ['2', 'Staff and/or trainee research activities documented'],
                ['3', 'Research findings disseminated (seminars, publications, shows)'],
            ]),
            self::areaItems('7.3', '7.3 Industry cooperation', [
                ['1', 'Active partnerships / MoUs with industry'],
                ['2', 'Industry participation in curriculum and assessment'],
                ['3', 'Joint projects, guest lectures or industry days conducted'],
            ]),
            self::areaItems('7.4', '7.4 Community engagement', [
                ['1', 'Community outreach / CSR activities undertaken'],
                ['2', 'Community needs inform institutional programmes'],
            ]),
            self::areaItems('7.5', '7.5 National and international linkages', [
                ['1', 'Linkages with other TVET institutions nationally'],
                ['2', 'International partnerships / exchange where applicable'],
            ]),
            self::areaItems('7.6', '7.6 Technology transfer', [
                ['1', 'Technology transfer / extension services to industry or community'],
                ['2', 'Evidence of applied technology solutions from institutional capacity'],
            ]),
        );

        return ['items' => $items, 'tables' => []];
    }

    /**
     * Human labels for sampling-table columns (wizard / PDF).
     *
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
                'size' => 'Size',
                'capacity_per_shift' => 'Capacity per shift',
                'accessibility_pwds' => 'Accessibility PWDs',
                'condition' => 'Condition',
                'furniture' => 'Furniture',
                'fit_for_programme' => 'Fit for programme',
                'adequacy' => 'Adequacy',
            ],
            'workshops_labs' => [
                'workshop_lab' => 'Workshop / Lab',
                'size' => 'Size',
                'capacity' => 'Capacity',
                'accessibility' => 'Accessibility',
                'condition' => 'Condition',
                'fit' => 'Fit',
                'adequacy' => 'Adequacy',
                'safety' => 'Safety',
                'workshop_layout' => 'Workshop Layout',
            ],
            'tools_equipment' => [
                'sampled_workshop' => 'Sampled Workshop',
                'relevant' => 'Relevant',
                'condition_functionality' => 'Condition / Functionality',
                'technician' => 'Technician',
                'inventory' => 'Inventory',
                'adequacy' => 'Adequacy',
                'organization_storage' => 'Organization / Storage',
                'installed_labelled' => 'Properly Installed / Labelled',
            ],
            'trainers_analysis' => [
                'name' => 'Name',
                'qualifications' => 'Qualifications',
                'experience' => 'Experience',
                'training_area' => 'Training area',
                'tveta_license_no' => 'TVETA License No.',
                'score' => 'Score',
                'remarks' => 'Remarks',
            ],
            'programmes_data' => [
                'courses' => 'Courses',
                'level' => 'Level',
                'exam_body' => 'Exam Body',
                'approved_enrolment' => 'Approved Enrolment',
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
            'theory_rooms' => '2.2 Theory rooms (sample — max 10)',
            'workshops_labs' => '2.3 Workshops / Labs (sample — max 10)',
            'tools_equipment' => '2.4 Tools and Equipment (sample — max 10)',
            'trainers_analysis' => '3.3 Trainers Analysis Template (max 10)',
            'programmes_data' => '5.1 Programmes data Sheet (max 10)',
        ];
    }

    /**
     * Merge posted section answers into the stored payload while preserving schema keys.
     *
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

        $itemsInput = is_array($input['items'] ?? null) ? $input['items'] : [];
        foreach ($sectionData['items'] as $i => $item) {
            $row = $itemsInput[$i] ?? $itemsInput[$item['key'] ?? ''] ?? [];
            if (! is_array($row)) {
                continue;
            }
            $sectionData['items'][$i]['target'] = (string) ($row['target'] ?? $item['target'] ?? '');
            $sectionData['items'][$i]['observations'] = (string) ($row['observations'] ?? $item['observations'] ?? '');
            $sectionData['items'][$i]['recommendations'] = (string) ($row['recommendations'] ?? $item['recommendations'] ?? '');
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
}
