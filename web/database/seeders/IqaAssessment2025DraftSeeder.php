<?php

namespace Database\Seeders;

use App\Models\Qa\IqaAssessment;
use App\Models\User;
use App\Services\Qa\IqaAssessmentSchema;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Creates a richly completed draft NATIONAL POLYTECHNIC QUALITY AUDIT TOOL
 * assessment for calendar year 2025 (status remains draft).
 */
class IqaAssessment2025DraftSeeder extends Seeder
{
    public function run(): void
    {
        $userId = $this->resolveQaOfficerUserId();
        $payload = $this->buildDetailedPayload();

        $existing = IqaAssessment::query()
            ->where('assessment_year', 2025)
            ->where('status', IqaAssessment::STATUS_DRAFT)
            ->orderByDesc('id')
            ->first();

        $attributes = [
            'title' => IqaAssessmentSchema::TITLE,
            'assessment_year' => 2025,
            'status' => IqaAssessment::STATUS_DRAFT,
            'current_section' => 4,
            'payload' => $payload,
            'updated_by_user_id' => $userId,
            'published_by_user_id' => null,
            'publisher_name' => null,
            'published_at' => null,
        ];

        if ($existing) {
            $existing->fill($attributes)->save();
            $this->command?->info("Updated draft IQA assessment #{$existing->id} for 2025.");

            return;
        }

        $assessment = IqaAssessment::query()->create(array_merge($attributes, [
            'created_by_user_id' => $userId,
        ]));

        $this->command?->info("Created draft IQA assessment #{$assessment->id} for 2025.");
    }

    private function resolveQaOfficerUserId(): ?int
    {
        $id = DB::table('user_roles as ur')
            ->join('roles as r', 'ur.role_id', '=', 'r.id')
            ->where('r.role_name', 'QA Officer')
            ->value('ur.user_id');

        if ($id) {
            return (int) $id;
        }

        return User::query()->orderBy('id')->value('id');
    }

    /**
     * @return array<string, mixed>
     */
    private function buildDetailedPayload(): array
    {
        $payload = IqaAssessmentSchema::emptyPayload();

        foreach (range(1, 7) as $section) {
            $key = (string) $section;
            $sectionData = $payload['sections'][$key];
            $sectionData['items'] = $this->fillItems($section, $sectionData['items']);
            $sectionData['tables'] = $this->fillTables($section, $sectionData['tables']);
            $sectionData['overall_recommendations'] = $this->overallRecommendations($section);
            $payload['sections'][$key] = $sectionData;
        }

        $payload['auditors'] = [
            ['name' => '', 'date' => '', 'signature' => ''],
            ['name' => '', 'date' => '', 'signature' => ''],
            ['name' => '', 'date' => '', 'signature' => ''],
        ];
        $payload['walkthrough'] = [];

        return $payload;
    }

    /**
     * @param  list<array<string, mixed>>  $items
     * @return list<array<string, mixed>>
     */
    private function fillItems(int $section, array $items): array
    {
        $seenArea = [];
        foreach ($items as $i => $item) {
            $area = (string) ($item['audit_area'] ?? '');
            $code = (string) ($item['area_code'] ?? '');
            $indicator = (string) ($item['indicator'] ?? '');
            $areaKey = $code !== '' ? $code : $area;
            $isFirst = ! isset($seenArea[$areaKey]);
            $seenArea[$areaKey] = true;

            if ($isFirst) {
                $items[$i]['observations'] = $this->observationsFor($section, $area !== '' ? $area : $code, $indicator, $code);
                $items[$i]['recommendations'] = $this->recommendationsFor($section, $area !== '' ? $area : $code, $code);
            } else {
                $items[$i]['observations'] = '';
                $items[$i]['recommendations'] = '';
            }
            unset($items[$i]['target']);
        }

        return $items;
    }

    private function targetFor(int $section, string $area, string $sno): string
    {
        return match (true) {
            str_contains($area, 'Strategic') => 'Current Strategic Plan 2023–2027 approved by Board; SMART objectives with annual KPIs; M&E reports each quarter.',
            str_contains($area, 'Board') => 'Functional Board meeting at least quarterly; signed minutes within 14 days; conflict-of-interest register updated each sitting.',
            str_contains($area, 'Senior Management') || str_contains($area, 'Academic board') => 'SMT monthly; Academic Board each semester; current organogram displayed; documented schemes of delegation.',
            str_contains($area, 'Internal quality') => 'IQA policy current; designated QA Officer; at least two internal audits per year; CAPA closed within agreed timelines.',
            str_contains($area, 'Administrative documents') => 'Controlled manuals versioned; records schedule implemented; registers complete for admissions, staff and assets.',
            str_contains($area, 'Legal documents') => 'Valid TVETA registration/accreditation; title/lease files complete; statutory certificates (fire, health, NEMA) within validity.',
            str_contains($area, 'Financial') => 'Board-approved budget; audited financial statements for prior FY; updated asset register; segregation of duties evidenced.',
            str_contains($area, 'Safety') => 'Safety policy; serviced extinguishers; marked exits; PPE enforced in workshops; trained first-aiders on duty roster.',
            str_contains($area, 'Sanitation') => 'Gender-separated toilets adequate to enrolment; PWD cubicle; soap/water; functional drainage and waste contract.',
            str_contains($area, 'Utilities') => 'Stable power with backup; potable water; campus Wi-Fi covering teaching blocks; lighting/ventilation meeting standards.',
            str_contains($area, 'Library') => 'Staffed resource centre open training hours; catalogue current; print + e-resources mapped to programmes.',
            str_contains($area, '3.1') || str_contains($area, 'Trainers') => 'Qualified, TVETA-licensed trainers; ratios within programme norms; CPD log and appraisals for the year.',
            str_contains($area, 'Support Staff') => 'Adequate technicians and admin support; JDs filed; appraisal cycle completed for FY.',
            str_contains($area, 'Timetable') => 'Approved master and class timetables covering theory and practical; circulated before term start.',
            str_contains($area, 'Attendance') => 'Daily class and trainer attendance registers; absenteeism followed within one week.',
            str_contains($area, 'professional documents') => 'Approved schemes of work and session plans for all units; materials aligned to occupational standards.',
            str_contains($area, 'Assessment') || str_contains($area, 'Examination') => 'Assessment policy applied; moderated papers; secure storage; results released per calendar.',
            str_contains($area, 'Industrial attachment') => 'Attachment policy; placements for eligible cohorts; supervised logbooks; industry feedback filed.',
            str_contains($area, 'Programmes evaluation') => 'TVETA-approved programmes; enrolment within capacity; annual programme review with industry input.',
            str_contains($area, 'Guidance') => 'Staffed G&C unit; confidential records; referral pathway to external services where needed.',
            str_contains($area, 'Health') => 'Sick-bay/referral MoU; first-aid coverage; wellness sessions each semester.',
            str_contains($area, 'Co-curricular') => 'Active sports/clubs calendar; supervised societies; basic recreation equipment available.',
            str_contains($area, 'Welfare') => 'Hardship/fee support guidelines; PWD accommodations; functional student council; complaints register.',
            str_contains($area, 'Innovation') => 'Innovation framework; at least two trainee/staff prototypes showcased annually.',
            str_contains($area, 'Research') => 'Research & ethics policy; documented staff/trainee projects; dissemination via seminar or show.',
            str_contains($area, 'Industry cooperation') => 'Active MoUs; industry guest sessions; joint projects or curriculum review meetings recorded.',
            str_contains($area, 'Community') => 'Documented outreach/CSR; community needs reflected in programme planning notes.',
            str_contains($area, 'National and international') => 'National TVET linkages; any exchange/partnership files current.',
            str_contains($area, 'Technology transfer') => 'Evidence of applied solutions or extension support to industry/community.',
            default => "Indicator {$sno} meets documented institutional standard for section {$section}.0 of the 2025 audit cycle.",
        };
    }

    private function observationsFor(int $section, string $area, string $indicator, string $sno): string
    {
        $base = match ($section) {
            1 => 'Document review and interviews with Principal, Board secretary and QA Officer (Feb–Mar 2025).',
            2 => 'Physical inspection of campus facilities and sampling of teaching spaces (March 2025).',
            3 => 'HR file sample, licence verification and trainer interviews (March–April 2025).',
            4 => 'Timetable audit, lesson observation sample and assessment records check (April 2025).',
            5 => 'Programme files, TVETA licences and enrolment reconciliations (May 2025).',
            6 => 'Trainee welfare walk-through and student council discussions (May 2025).',
            7 => 'Partnership files, innovation show records and outreach reports (June 2025).',
            default => 'On-site verification during the 2025 institutional quality audit.',
        };

        $detail = match (true) {
            $section === 1 && str_contains($area, 'Strategic') => ' Strategic Plan 2023–2027 binder sighted with Board approval minute 14/2023; Q1–Q4 2024 M&E dashboards filed. Annual work plans for Academics, Finance and Admin cascade objectives. Vision/mission displayed at main gate and reception.',
            $section === 1 && str_contains($area, 'Board') => ' Board met four times in 2024; attendance registers and signed minutes complete through Dec 2024. Conflict-of-interest declarations renewed January 2025. One induction session for new members held Aug 2024.',
            $section === 1 && str_contains($area, 'Financial') => ' FY 2023/24 external audit opinion unqualified; Board budget for 2024/25 approved. Asset register last updated Jan 2025. Petty cash and procurement dual-control evidenced on sample vouchers.',
            $section === 2 && str_contains($area, 'Safety') => ' Extinguishers serviced Jan 2025 (tags current). Assembly point marked near sports field. Welding and electrical workshops display safety rules; PPE issue register maintained by technicians.',
            $section === 2 && str_contains($area, 'Library') => ' Library seats ~48; KOHA catalogue in use; e-resources via Kenya Library Consortium. Collection mapped to CHP, Nursing and ICT programmes; evening access Mon–Thu until 19:00.',
            $section === 3 => ' Sample of 10 trainers reviewed: academic certificates and TVETA licences on file. CPD hours logged for pedagogy and CBET orientation. Appraisal forms signed by HODs for 2024 cycle.',
            $section === 4 && str_contains($area, 'Assessment') => ' Continuous assessment schedules posted; moderation minutes for Dec 2024 and April 2025 exams available. Strong-room access log kept by Academic Registrar.',
            $section === 4 && str_contains($area, 'Industrial') => ' 86% of eligible Level 5/6 trainees placed in 2024/25. Logbooks sampled (n=12) show supervisor signatures. Industry feedback summary shared with HODs.',
            $section === 5 => ' All listed programmes hold current TVETA approvals. Enrolment vs approved capacity within limits except one oversubscribed short course (remedial intake freeze noted). Programme review minutes May 2025 on file.',
            $section === 6 => ' G&C officer available full-time; confidential cabinet locked. Student council elections held Feb 2025. Complaints register shows 7 entries YTD with closure notes.',
            $section === 7 => ' Three MoUs with hospitals/industry partners active. Innovation day June 2025 showcased six trainee projects. Community health outreach conducted in Q1 and Q2 2025.',
            default => ' Evidence reviewed against indicator “'.$indicator.'”. Documentation and practice largely consistent with institutional procedures for 2025.',
        };

        $gap = match ((int) $sno % 5) {
            0 => ' Minor filing delay noted on the latest update cycle; corrective filing requested within 30 days.',
            1 => ' Overall satisfactory; maintain current controls.',
            2 => ' Partial evidence for the most recent quarter; follow-up verification scheduled.',
            3 => ' Met with observation: display materials need refresh in one teaching block.',
            default => ' Compliant for the audit period with continuous improvement noted.',
        };

        return trim($base.$detail.' '.$gap);
    }

    private function recommendationsFor(int $section, string $area, string $sno): string
    {
        return match (true) {
            str_contains($area, 'Strategic') => 'Publish mid-term Strategic Plan review summary to staff intranet by end of Q3 2025; strengthen KPI ownership cards per department.',
            str_contains($area, 'Board') => 'Schedule annual Board self-evaluation; archive electronic copies of signed minutes in the document repository.',
            str_contains($area, 'Internal quality') => 'Close open CAPAs older than 90 days; present IQA dashboard to Board each quarter.',
            str_contains($area, 'Safety') => 'Conduct full emergency drill each semester; update first-aider roster after staff transfers.',
            str_contains($area, 'Library') => 'Increase programme-specific e-book titles for new CBET units; track utilisation monthly.',
            str_contains($area, 'Trainers') || str_contains($area, '3.1') => 'Prioritise TVETA licence renewals 60 days before expiry; expand industrial attachment for trainers lacking recent industry exposure.',
            str_contains($area, 'Support Staff') => 'Fill vacant workshop technician post in Electrical; complete JD refresh for registry clerks.',
            str_contains($area, 'Timetable') => 'Reduce clashes in shared labs by locking practical blocks two weeks before term.',
            str_contains($area, 'Assessment') => 'Digitise moderation tracker; enforce 10-working-day feedback SLA for continuous assessment.',
            str_contains($area, 'Industrial') => 'Grow partner pool in western Kenya; introduce mid-attachment monitoring calls.',
            str_contains($area, 'Programmes') => 'Document industry advisory panel inputs for each major programme annually; align marketing flyers to licensed titles only.',
            str_contains($area, 'Guidance') || str_contains($area, 'Welfare') => 'Train peer counsellors; publish anonymised welfare outcomes in the annual QA report.',
            str_contains($area, 'Innovation') || str_contains($area, 'Research') => 'Ring-fence a small innovation fund; require ethics clearance numbers on all trainee projects.',
            str_contains($area, 'Industry') || str_contains($area, 'Community') => 'Renew MoUs before anniversary dates; log outreach hours against departmental work plans.',
            default => 'Sustain current practice; include indicator '.$sno.' in the next internal surveillance checklist for section '.$section.'.0.',
        };
    }

    /**
     * @param  array<string, array<string, mixed>>  $tables
     * @return array<string, array<string, mixed>>
     */
    private function fillTables(int $section, array $tables): array
    {
        if ($section === 2) {
            $tables['admin_offices'] = $this->fillAdminOffices($tables['admin_offices']);
            $tables['theory_rooms'] = $this->fillTheoryRooms($tables['theory_rooms']);
            $tables['workshops_labs'] = $this->fillWorkshops($tables['workshops_labs']);
            $tables['tools_equipment'] = $this->fillTools($tables['tools_equipment']);
        }

        if ($section === 3) {
            $tables['trainers_analysis'] = $this->fillTrainers($tables['trainers_analysis']);
        }

        if ($section === 5) {
            $tables['programmes_data'] = $this->fillProgrammes($tables['programmes_data']);
        }

        return $tables;
    }

    /**
     * @param  array<string, mixed>  $table
     * @return array<string, mixed>
     */
    private function fillAdminOffices(array $table): array
    {
        $data = [
            ["Principal's Office", '28', 'Good — repainted 2024', 'Yes — private meetings and document storage adequate', 'Ramp access to admin block; doorway width OK', 'Executive desk, visitor chairs, filing cabinets, IT workstation'],
            ['Staff room', '42', 'Fair — ceiling panels aged', 'Yes for briefing and marking', 'Ground floor; no dedicated PWD WC adjacent', 'Tables for 18, lockers, notice boards, kettle station'],
            ['G&C room', '16', 'Good — sound-proofed door fitted', 'Yes — confidential counselling', 'Level access from courtyard', 'Desk, two easy chairs, lockable cabinet, privacy curtains'],
        ];

        foreach ($table['rows'] as $i => $row) {
            if (! isset($data[$i])) {
                continue;
            }
            [$offices, $size, $condition, $fit, $access, $furniture] = $data[$i];
            $table['rows'][$i] = [
                'offices' => $offices,
                'size_m' => $size,
                'condition' => $condition,
                'fit_for_purpose' => $fit,
                'accessibility_pwds' => $access,
                'furniture' => $furniture,
            ];
        }

        $table['remarks'] = 'Admin block inspected 12 Mar 2025. Recommend staff-room ceiling refresh and PWD WC near staff common room in FY 2025/26 works plan.';

        return $table;
    }

    /**
     * @param  array<string, mixed>  $table
     * @return array<string, mixed>
     */
    private function fillTheoryRooms(array $table): array
    {
        $rooms = [
            ['LT-01 Main lecture theatre', '120 m²', '90', 'Ramp + front row space', 'Good', 'Fixed seats + AV lectern', 'Cross-programme theory', 'Adequate'],
            ['CR-A201 Community Health', '48 m²', '36', 'Ground floor, wide door', 'Good', 'Desks, whiteboard, projector', 'CHP / Public Health', 'Adequate'],
            ['CR-A202 Nursing theory', '52 m²', '40', 'Lift access from lobby', 'Good', 'Desks, charts rail, projector', 'Nursing / Midwifery', 'Adequate'],
            ['CR-B101 ICT theory', '45 m²', '32', 'Step at entrance — temporary ramp', 'Fair', 'Desktops along walls + desks', 'ICT / Computer packages', 'Borderline at peak'],
            ['CR-B102 Business studies', '40 m²', '30', 'First floor, stair only', 'Fair', 'Desks, whiteboard', 'Business / Entrepreneurship', 'Adequate'],
            ['CR-C201 Nutrition', '38 m²', '28', 'Ground floor', 'Good', 'Desks, demonstration counter', 'Nutrition & Dietetics', 'Adequate'],
            ['CR-C202 Environmental health', '36 m²', '26', 'Ground floor', 'Good', 'Desks, maps cabinet', 'Environmental Health', 'Adequate'],
            ['Seminar S-1', '24 m²', '16', 'Level access', 'Good', 'U-shape tables, screen', 'Tutorials / CPD', 'Adequate'],
            ['Seminar S-2', '22 m²', '14', 'Level access', 'Good', 'Boardroom table', 'Board / Academic Board overflow', 'Adequate'],
            ['Open learning hub', '60 m²', '40', 'Double doors, ramp', 'Good', 'Modular desks, charging points', 'Blended / make-up classes', 'Adequate'],
        ];

        foreach ($table['rows'] as $i => $row) {
            if (! isset($rooms[$i])) {
                continue;
            }
            [$name, $size, $cap, $access, $condition, $furniture, $fit, $adequacy] = $rooms[$i];
            $table['rows'][$i] = [
                'lecture_rooms' => $name,
                'size' => $size,
                'capacity_per_shift' => $cap,
                'accessibility_pwds' => $access,
                'condition' => $condition,
                'furniture' => $furniture,
                'fit_for_programme' => $fit,
                'adequacy' => $adequacy,
            ];
        }

        $table['remarks'] = 'Sample of 10 theory spaces (full list held in estates register). Priority: permanent ramp at CR-B101 and stair-lift assessment for CR-B102.';

        return $table;
    }

    /**
     * @param  array<string, mixed>  $table
     * @return array<string, mixed>
     */
    private function fillWorkshops(array $table): array
    {
        $rows = [
            ['Skills lab — Nursing', '85 m²', '24', 'Wide doors', 'Good', 'Yes — manikins & beds', 'Adequate', 'Sharps protocol posted', 'Stations clearly zoned'],
            ['Skills lab — Midwifery', '70 m²', '18', 'Wide doors', 'Good', 'Yes', 'Adequate', 'Good', 'Birthing simulators area marked'],
            ['Community Health field store/lab', '40 m²', '16', 'Ground', 'Fair', 'Yes for kit prep', 'Tight at peak', 'Chemicals locked', 'Shelving needs labelling'],
            ['Nutrition kitchen lab', '55 m²', '20', 'Ground', 'Good', 'Yes', 'Adequate', 'Fire blanket + extinguisher', 'Wet/dry zones separated'],
            ['ICT networking lab', '48 m²', '24', 'First floor', 'Good', 'Yes', 'Adequate', 'UPS installed', 'Cable trays neat'],
            ['Computer applications lab', '50 m²', '28', 'First floor', 'Good', 'Yes', 'Adequate at off-peak', 'Good', 'Rows facing projector'],
            ['Environmental health lab', '42 m²', '16', 'Ground', 'Fair', 'Yes', 'Adequate', 'Eyewash available', 'Benches aged but serviceable'],
            ['Electrical workshop', '60 m²', '16', 'Ground ramp', 'Fair', 'Yes for basic wiring', 'Needs more benches', 'PPE mandatory', 'Workbenches left/right aisle'],
            ['Fabrication / metal workshop', '75 m²', '12', 'Double door', 'Fair', 'Limited to light fab', 'Equipment ageing', 'Guards on grinders', 'Open plan with marked walkways'],
            ['Multi-purpose demonstration hall', '110 m²', '60', 'Ramp', 'Good', 'Cross-department demos', 'Adequate', 'Exits clear', 'Flexible seating'],
        ];

        foreach ($table['rows'] as $i => $row) {
            if (! isset($rows[$i])) {
                continue;
            }
            [$name, $size, $cap, $access, $condition, $fit, $adequacy, $safety, $layout] = $rows[$i];
            $table['rows'][$i] = [
                'workshop_lab' => $name,
                'size' => $size,
                'capacity' => $cap,
                'accessibility' => $access,
                'condition' => $condition,
                'fit' => $fit,
                'adequacy' => $adequacy,
                'safety' => $safety,
                'workshop_layout' => $layout,
            ];
        }

        $table['remarks'] = 'Workshops sampled 14–15 Mar 2025. Capital priority: additional nursing manikins and electrical workshop benches before Sept 2025 intake.';

        return $table;
    }

    /**
     * @param  array<string, mixed>  $table
     * @return array<string, mixed>
     */
    private function fillTools(array $table): array
    {
        $rows = [
            ['Nursing skills lab', 'Yes — clinical kits', 'Good / calibrated BP sets', 'Yes — 2 technicians', 'Inventory Dec 2024', 'Adequate for approved numbers', 'Locked cabinets labelled', 'Beds & simulators tagged'],
            ['Midwifery lab', 'Yes', 'Good', 'Shared with Nursing', 'Inventory Dec 2024', 'Need 2 more delivery kits', 'Organised', 'Yes'],
            ['CHP field kits', 'Yes — outreach bags', 'Fair — some BP cuffs worn', 'CHP technician', 'Spot check Mar 2025', 'Reorder gloves & MUAC tapes', 'Shelves labelled by team', 'Bags numbered'],
            ['Nutrition kitchen', 'Yes', 'Good appliances', 'Kitchen attendant', 'Yes', 'Adequate', 'Dry store locked', 'Equipment asset-tagged'],
            ['ICT networking', 'Yes — routers/switches', 'Good', 'ICT support officer', 'CMDB updated', 'Adequate for class of 24', 'Patch panels labelled', 'Racks labelled'],
            ['Computer lab', 'Yes — PCs', 'Good (2023 refresh)', 'ICT support', 'Asset register', '28 seats — peak waitlist', 'Cable management OK', 'Hostname labels'],
            ['Environmental health', 'Yes — sampling kits', 'Fair', 'Lab assistant', 'Partial', 'Need water test reagents', 'Chemicals segregated', 'Mostly labelled'],
            ['Electrical workshop', 'Yes — hand tools & meters', 'Mixed — meters OK, irons aged', 'Workshop technician', 'Tool board shadow', 'Short on insulated tools sets', 'Shadow board in use', 'Yes'],
            ['Fabrication', 'Partial — light fab only', 'Ageing welders', 'Part-time technician', 'Incomplete', 'Not adequate for heavy fab', 'Open racks', 'Partial labelling'],
            ['Demo hall AV', 'Yes — projector/PA', 'Good', 'Estates/ICT', 'Yes', 'Adequate', 'AV cupboard locked', 'Yes'],
        ];

        foreach ($table['rows'] as $i => $row) {
            if (! isset($rows[$i])) {
                continue;
            }
            [$shop, $rel, $cond, $tech, $inv, $adeq, $org, $label] = $rows[$i];
            $table['rows'][$i] = [
                'sampled_workshop' => $shop,
                'relevant' => $rel,
                'condition_functionality' => $cond,
                'technician' => $tech,
                'inventory' => $inv,
                'adequacy' => $adeq,
                'organization_storage' => $org,
                'installed_labelled' => $label,
            ];
        }

        $table['remarks'] = 'Tools/equipment sample aligns with programme priorities. Procurement list raised for midwifery kits, CHP cuffs, electrical insulated sets and fabrication upgrades.';

        return $table;
    }

    /**
     * @param  array<string, mixed>  $table
     * @return array<string, mixed>
     */
    private function fillTrainers(array $table): array
    {
        $rows = [
            ['Jane Wanjiku Mwangi', 'MSc Nursing; BScN', '12 yrs clinical + 6 teaching', 'Nursing / Midwifery', 'TVETA/TR/2019/08421', '4.6 / 5', 'Strong pedagogy; mentor for juniors'],
            ['Peter Otieno Okello', ' MPH; BSc Environmental Health', '10 yrs field + 5 teaching', 'Environmental Health', 'TVETA/TR/2018/07102', '4.4 / 5', 'Leads community attachments'],
            ['Grace Akinyi Otieno', 'BSc Nutrition & Dietetics; PGDE', '8 yrs', 'Nutrition', 'TVETA/TR/2020/10233', '4.3 / 5', 'Kitchen lab custodian'],
            ['Samuel Kiprono Cheruiyot', 'BSc Computer Science; CCNA', '7 yrs industry + 4 teaching', 'ICT / Networking', 'TVETA/TR/2021/11890', '4.5 / 5', 'Maintains networking lab'],
            ['Mercy Njeri Kamau', 'Higher Diploma CHP; BSc PH', '9 yrs', 'Community Health', 'TVETA/TR/2017/05518', '4.2 / 5', 'Coordinates outreach'],
            ['Daniel Mutua Kyalo', 'Dip Electrical; Craft Certificate', '15 yrs industry + 3 teaching', 'Electrical workshop', 'TVETA/TR/2022/13044', '3.9 / 5', 'Needs pedagogy CPD this year'],
            ['Amina Hassan Mohamed', 'BSc Midwifery', '6 yrs', 'Midwifery skills', 'TVETA/TR/2020/09917', '4.4 / 5', 'Excellent skills-lab facilitation'],
            ['John Mwangi Kariuki', 'MBA; BCom', '11 yrs', 'Business / Entrepreneurship', 'TVETA/TR/2019/08801', '4.1 / 5', 'Industry guest network strong'],
            ['Lucy Chebet Langat', 'BEd Science; Dip ICT', '5 yrs', 'Computer applications', 'TVETA/TR/2023/14102', '4.0 / 5', 'New licence — monitor CPD'],
            ['Brian Ouma Odhiambo', 'BSc Public Health', '4 yrs', 'CHP theory', 'TVETA/TR/2024/15220', '3.8 / 5', 'Early-career — paired mentoring'],
        ];

        foreach ($table['rows'] as $i => $row) {
            if (! isset($rows[$i])) {
                continue;
            }
            [$name, $qual, $exp, $area, $licence, $score, $remarks] = $rows[$i];
            $table['rows'][$i] = [
                'name' => $name,
                'qualifications' => $qual,
                'experience' => $exp,
                'training_area' => $area,
                'tveta_license_no' => $licence,
                'score' => $score,
                'remarks' => $remarks,
            ];
        }

        $table['remarks'] = 'Trainers analysis template completed for a sample of 10 (full establishment list held in HR). Average appraisal score 4.2/5 for 2024. Licence renewals tracked on HR dashboard.';

        return $table;
    }

    /**
     * @param  array<string, mixed>  $table
     * @return array<string, mixed>
     */
    private function fillProgrammes(array $table): array
    {
        $rows = [
            ['Community Health Practitioner', 'Level 5', 'TVET CDACC', '120', 'Yes — 4 trainers', 'Yes — classroom + field kits', '4.5', '48', '62', '110', 'Within capacity; strong demand'],
            ['Community Health Practitioner', 'Level 6', 'TVET CDACC', '80', 'Yes — 3 trainers', 'Yes', '4.5', '28', '36', '64', 'Attachment partners sufficient'],
            ['Nursing', 'Diploma', 'NCK / institutional', '60', 'Yes — 5 trainers', 'Skills labs adequate', '4.7', '18', '34', '52', 'Clinical sites MoU current'],
            ['Midwifery', 'Diploma', 'NCK / institutional', '40', 'Yes — 3 trainers', 'Skills lab shared', '4.6', '6', '28', '34', 'Need extra delivery kits'],
            ['Nutrition & Dietetics', 'Level 5', 'TVET CDACC', '50', 'Yes — 2 trainers', 'Kitchen lab yes', '4.3', '12', '30', '42', 'Kitchen capacity OK'],
            ['Environmental Health', 'Level 5', 'TVET CDACC', '45', 'Yes — 2 trainers', 'Lab fair', '4.2', '20', '18', '38', 'Reagent stock low'],
            ['ICT Technician', 'Level 5', 'TVET CDACC', '60', 'Yes — 3 trainers', 'Two labs', '4.4', '34', '22', '56', 'Peak lab pressure'],
            ['Computer Applications', 'Certificate', 'Institutional / KNEC path', '80', 'Yes — 2 trainers', 'One lab', '4.0', '40', '35', '75', 'Near capacity'],
            ['Business Management', 'Level 5', 'TVET CDACC', '50', 'Yes — 2 trainers', 'Classrooms yes', '4.1', '22', '24', '46', 'Stable enrolment'],
            ['Entrepreneurship short course', 'Short', 'Institutional', '40', 'Part-time pool', 'Classroom', '3.8', '15', '18', '33', 'Freeze extra intake until review'],
        ];

        foreach ($table['rows'] as $i => $row) {
            if (! isset($rows[$i])) {
                continue;
            }
            [$courses, $level, $exam, $approved, $trainers, $facilities, $score, $m, $f, $total, $remarks] = $rows[$i];
            $table['rows'][$i] = [
                'courses' => $courses,
                'level' => $level,
                'exam_body' => $exam,
                'approved_enrolment' => $approved,
                'trainers_availability' => $trainers,
                'adequate_facilities' => $facilities,
                'licensed_tveta_score' => $score,
                'enrolment_m' => $m,
                'enrolment_f' => $f,
                'enrolment_total' => $total,
                'remarks' => $remarks,
            ];
        }

        $table['remarks'] = 'Programmes data sheet reflects May 2025 snapshot against TVETA approvals and Academic Registrar enrolment extracts. Short-course entrepreneurship temporarily capped pending curriculum alignment review.';

        return $table;
    }

    private function overallRecommendations(int $section): string
    {
        return match ($section) {
            1 => "Leadership and governance frameworks for the 2025 cycle are substantially in place. Sustain quarterly Board and SMT rhythms, close ageing CAPAs, and ensure the mid-term Strategic Plan review is formally minuted and shared with departments before Q4 2025.",
            2 => "Physical resources largely support current enrolment. Prioritise accessibility upgrades (CR-B101/B102), electrical workshop benches, midwifery kits and staff-room maintenance in the 2025/26 estates and procurement plans. Keep extinguisher and utility preventive maintenance on schedule.",
            3 => "Trainer qualifications and licensing are generally strong. Complete pedagogy CPD for workshop-based trainers, fill the electrical technician gap, and keep TVETA licence renewals on a 60-day early-warning list managed by HR and QA jointly.",
            4 => "Training delivery controls (timetable, attendance, professional documents, assessment and attachment) are operating. Tighten lab clash resolution, digitise moderation tracking, and expand mid-attachment monitoring with industry partners.",
            5 => "Programmes remain within licensed scope with healthy enrolment. Document annual industry advisory inputs per major programme, address midwifery kit shortfalls, and hold the entrepreneurship short-course intake freeze until curriculum alignment is confirmed.",
            6 => "Trainee support services are functional. Strengthen peer-counsellor capacity, publish anonymised welfare indicators in the annual QA report, and ensure PWD accommodations are audited each semester alongside estates works.",
            7 => "Innovation, research and partnerships show tangible 2025 activity. Ring-fence a modest innovation fund, renew MoUs before anniversary dates, and systematically log community outreach against departmental work plans for the next surveillance visit.",
            default => '',
        };
    }
}
