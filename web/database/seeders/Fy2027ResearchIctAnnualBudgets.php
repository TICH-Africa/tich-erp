<?php

namespace Database\Seeders;

use App\Models\Administration\PlanningCycle;
use App\Models\Department;
use App\Models\User;
use App\Services\Administration\AdministrationService;
use Illuminate\Database\Seeder;

/**
 * Inserts FY2027 annual department budgets for Research and ICT
 * under the "2027 Financial Budgeting" planning cycle.
 */
class Fy2027ResearchIctAnnualBudgets extends Seeder
{
    public const QUARTER_LABELS = [
        'q1' => 'Q1 · Jan–Mar',
        'q2' => 'Q2 · Apr–Jun',
        'q3' => 'Q3 · Jul–Sep',
        'q4' => 'Q4 · Oct–Dec',
    ];

    public function run(): void
    {
        $cycle = PlanningCycle::query()
            ->where('fiscal_year', 2027)
            ->where(function ($q) {
                $q->where('title', 'like', '%2027 Financial Budget%')
                    ->orWhere('title', 'like', '%2027 Financial Budgetting%');
            })
            ->orderByDesc('id')
            ->first();

        if (! $cycle) {
            $cycle = PlanningCycle::query()->create([
                'cycle_code' => 'PLC-2027-FB',
                'title' => '2027 Financial Budgeting',
                'plan_tier' => 'annual',
                'fiscal_year' => 2027,
                'status' => 'open',
            ]);
        } elseif ($cycle->title !== '2027 Financial Budgeting') {
            $cycle->update(['title' => '2027 Financial Budgeting']);
        }

        $research = Department::query()->where('dept_code', 'RES')->firstOrFail();
        $ict = Department::query()
            ->whereIn('dept_code', ['ICTO', 'ICT'])
            ->orderByRaw("FIELD(dept_code, 'ICTO', 'ICT')")
            ->firstOrFail();

        $userId = (int) (User::query()->where('email', 'admin@tich.ac.ke')->value('id')
            ?? User::query()->orderBy('id')->value('id'));

        $admin = app(AdministrationService::class);

        foreach ([
            $this->researchBudget($research->id, $cycle->id),
            $this->ictBudget($ict->id, $cycle->id),
        ] as $payload) {
            $existing = \App\Models\Administration\BudgetRequest::query()
                ->where('department_id', $payload['department_id'])
                ->where('planning_cycle_id', $cycle->id)
                ->where('title', $payload['title'])
                ->first();

            if ($existing) {
                $existing->update([
                    'framework' => 'standard',
                    'budget_type' => 'annual',
                    'standard_line_items' => $payload['standard_line_items'],
                    'requested_amount' => $payload['requested_amount'],
                    'justification' => $payload['justification'],
                    'status' => 'submitted',
                    'submitted_by' => $userId,
                    'submitted_at' => $existing->submitted_at ?? now(),
                ]);
                $this->command?->info("Updated {$payload['title']} ({$existing->request_code})");
                continue;
            }

            $created = $admin->createBudgetRequest($payload, $userId);
            $this->command?->info("Created {$created->title} ({$created->request_code}) — KES ".number_format((float) $created->requested_amount, 2));
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function researchBudget(int $departmentId, int $cycleId): array
    {
        $quarters = [
            'q1' => [
                'income' => [
                    ['source' => 'NACOSTI / national competitive research grant (year-1 tranche)', 'amount' => 1850000],
                    ['source' => 'Partner co-funding — community health systems study', 'amount' => 620000],
                    ['source' => 'Institutional research facilitation allocation', 'amount' => 450000],
                    ['source' => 'Short course fees — research methods & proposal writing', 'amount' => 280000],
                    ['source' => 'Consultancy — baseline survey for county partner', 'amount' => 390000],
                ],
                'expenditure' => [
                    ['item' => 'Research assistant stipends (field cohort, 6×3 months)', 'quantity' => 6, 'description' => 'Q1 field mobilisation and household listing', 'unit_price' => 45000, 'unit_of_measure' => 'person'],
                    ['item' => 'Ethics review & protocol amendments', 'quantity' => 1, 'description' => 'IRB/ERC fees, consent tools printing', 'unit_price' => 185000, 'unit_of_measure' => 'lot'],
                    ['item' => 'Field tablets & offline data kit refresh', 'quantity' => 8, 'description' => 'Rugged tablets, cases, power banks', 'unit_price' => 42000, 'unit_of_measure' => 'unit'],
                    ['item' => 'Enumerator training workshop (3 days)', 'quantity' => 1, 'description' => 'Venue, facilitation, participant packs', 'unit_price' => 265000, 'unit_of_measure' => 'event'],
                    ['item' => 'County inception meetings & stakeholder mapping', 'quantity' => 3, 'description' => 'Travel, per diem, meeting costs', 'unit_price' => 95000, 'unit_of_measure' => 'county'],
                    ['item' => 'Literature access & reference manager licences', 'quantity' => 1, 'description' => 'Annual share for Q1 activation', 'unit_price' => 120000, 'unit_of_measure' => 'lot'],
                    ['item' => 'Lab/consumables — sample prep & cold-chain start-up', 'quantity' => 1, 'description' => 'Reagents, PPE, cool boxes', 'unit_price' => 310000, 'unit_of_measure' => 'lot'],
                    ['item' => 'Internal peer-review clinic facilitation', 'quantity' => 2, 'description' => 'Honoraria and materials for design critiques', 'unit_price' => 45000, 'unit_of_measure' => 'session'],
                ],
            ],
            'q2' => [
                'income' => [
                    ['source' => 'NACOSTI / national competitive research grant (year-1 tranche 2)', 'amount' => 1450000],
                    ['source' => 'Publication processing support from partner consortium', 'amount' => 210000],
                    ['source' => 'Institutional research facilitation allocation', 'amount' => 450000],
                    ['source' => 'Contract research — programme evaluation mid-line', 'amount' => 780000],
                    ['source' => 'Graduate research supervision levy (departmental share)', 'amount' => 165000],
                ],
                'expenditure' => [
                    ['item' => 'Field data collection — Wave 1 transport & logistics', 'quantity' => 1, 'description' => 'Vehicle hire, fuel, county guides', 'unit_price' => 540000, 'unit_of_measure' => 'lot'],
                    ['item' => 'Research assistant stipends (field cohort)', 'quantity' => 6, 'description' => 'Wave 1 interviewing and data upload', 'unit_price' => 45000, 'unit_of_measure' => 'person'],
                    ['item' => 'Community mobilisation & respondent compensation', 'quantity' => 450, 'description' => 'Token appreciation and venue hire in clusters', 'unit_price' => 800, 'unit_of_measure' => 'respondent'],
                    ['item' => 'Data quality audits & double-entry verification', 'quantity' => 1, 'description' => 'Supervisors, spot-checks, cleaning sprints', 'unit_price' => 195000, 'unit_of_measure' => 'lot'],
                    ['item' => 'Statistical software & analysis workstation time', 'quantity' => 1, 'description' => 'SPSS/Stata/R support and secure workspace', 'unit_price' => 175000, 'unit_of_measure' => 'lot'],
                    ['item' => 'Mid-line partner review workshop', 'quantity' => 1, 'description' => 'Findings clinic with county & NGO partners', 'unit_price' => 285000, 'unit_of_measure' => 'event'],
                    ['item' => 'Open-access APC reserve (pipeline manuscripts)', 'quantity' => 2, 'description' => 'Article processing charges deposit', 'unit_price' => 140000, 'unit_of_measure' => 'paper'],
                    ['item' => 'Field insurance & incident contingency', 'quantity' => 1, 'description' => 'Cover for teams during Wave 1', 'unit_price' => 98000, 'unit_of_measure' => 'lot'],
                ],
            ],
            'q3' => [
                'income' => [
                    ['source' => 'International collaborative grant — capacity strengthening arm', 'amount' => 2100000],
                    ['source' => 'Institutional research facilitation allocation', 'amount' => 450000],
                    ['source' => 'Industry / NGO consultancy — rapid evidence brief', 'amount' => 520000],
                    ['source' => 'Conference exhibition & sponsorship income', 'amount' => 180000],
                    ['source' => 'Short course fees — qualitative analysis bootcamp', 'amount' => 240000],
                ],
                'expenditure' => [
                    ['item' => 'Wave 2 fieldwork & follow-up interviews', 'quantity' => 1, 'description' => 'Travel, lodging, transcription support', 'unit_price' => 620000, 'unit_of_measure' => 'lot'],
                    ['item' => 'Research assistant & junior analyst stipends', 'quantity' => 5, 'description' => 'Coding, cleaning, and dashboard prep', 'unit_price' => 48000, 'unit_of_measure' => 'person'],
                    ['item' => 'Transcription & translation services', 'quantity' => 120, 'description' => 'KIIs and FGDs across languages', 'unit_price' => 2500, 'unit_of_measure' => 'hour'],
                    ['item' => 'National research symposium participation', 'quantity' => 4, 'description' => 'Registration, travel, accommodation', 'unit_price' => 85000, 'unit_of_measure' => 'delegate'],
                    ['item' => 'Manuscript development retreat (2 days)', 'quantity' => 1, 'description' => 'Writing facilitation and editing support', 'unit_price' => 240000, 'unit_of_measure' => 'event'],
                    ['item' => 'Knowledge translation briefs & policy notes', 'quantity' => 6, 'description' => 'Design, print, and digital dissemination', 'unit_price' => 35000, 'unit_of_measure' => 'brief'],
                    ['item' => 'Laboratory confirmatory assays (sub-study)', 'quantity' => 1, 'description' => 'Outsourced assays and courier', 'unit_price' => 410000, 'unit_of_measure' => 'lot'],
                    ['item' => 'Research mentorship clinic for early-career staff', 'quantity' => 3, 'description' => 'External mentors and materials', 'unit_price' => 65000, 'unit_of_measure' => 'session'],
                ],
            ],
            'q4' => [
                'income' => [
                    ['source' => 'Grant close-out / final disbursement (multi-year portfolio)', 'amount' => 980000],
                    ['source' => 'Institutional research facilitation allocation', 'amount' => 450000],
                    ['source' => 'End-year partner co-funding for dissemination', 'amount' => 360000],
                    ['source' => 'Contract research — end-line evaluation', 'amount' => 690000],
                    ['source' => 'Training of trainers fees — research ethics refresher', 'amount' => 195000],
                ],
                'expenditure' => [
                    ['item' => 'End-line analysis & technical report production', 'quantity' => 1, 'description' => 'Lead analyst time, graphics, peer review', 'unit_price' => 480000, 'unit_of_measure' => 'lot'],
                    ['item' => 'National dissemination conference (host)', 'quantity' => 1, 'description' => 'Venue, AV, printing, participant logistics', 'unit_price' => 720000, 'unit_of_measure' => 'event'],
                    ['item' => 'Community feedback forums (return of results)', 'quantity' => 4, 'description' => 'County forums and radio spots', 'unit_price' => 110000, 'unit_of_measure' => 'forum'],
                    ['item' => 'Open-access publishing & repository deposit', 'quantity' => 3, 'description' => 'APC, DOI, institutional repository', 'unit_price' => 155000, 'unit_of_measure' => 'paper'],
                    ['item' => 'Data archiving, de-identification & stewardship', 'quantity' => 1, 'description' => 'Secure storage, documentation, DMP close-out', 'unit_price' => 210000, 'unit_of_measure' => 'lot'],
                    ['item' => 'Equipment maintenance & calibration (field kits)', 'quantity' => 1, 'description' => 'Service contracts and replacements', 'unit_price' => 165000, 'unit_of_measure' => 'lot'],
                    ['item' => 'Annual research performance review retreat', 'quantity' => 1, 'description' => 'Team review, FY2028 pipeline planning', 'unit_price' => 275000, 'unit_of_measure' => 'event'],
                    ['item' => 'Audit-ready documentation & compliance pack', 'quantity' => 1, 'description' => 'Grant files, asset register, close-out binders', 'unit_price' => 125000, 'unit_of_measure' => 'lot'],
                ],
            ],
        ];

        [$payload, $total] = $this->buildAnnualPayload($quarters);

        return [
            'planning_cycle_id' => $cycleId,
            'department_id' => $departmentId,
            'title' => 'FY2027 Research Department Annual Budget',
            'framework' => 'standard',
            'budget_type' => 'annual',
            'requested_amount' => $total,
            'standard_line_items' => $payload,
            'justification' => "Annual Research Department financial plan for fiscal year 2027 under the 2027 Financial Budgeting cycle.\n\n"
                ."Priorities: (1) deliver multi-site community health and systems research; (2) grow competitive and collaborative grant income; "
               ."(3) strengthen ethics, data quality, and open dissemination; (4) build early-career research capacity; "
               ."(5) convert evidence into policy and practice briefs for county and partner audiences.\n\n"
               .'Quarterly income mixes grant tranches, institutional facilitation, consultancy, and short courses. '
                .'Expenditure follows the research cycle—protocol and ethics (Q1), Wave 1 fieldwork (Q2), Wave 2 analysis and knowledge products (Q3), and end-line dissemination with compliance close-out (Q4).',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function ictBudget(int $departmentId, int $cycleId): array
    {
        $quarters = [
            'q1' => [
                'income' => [
                    ['source' => 'Institutional ICT operations allocation', 'amount' => 2100000],
                    ['source' => 'Staff / student device support & repair desk fees', 'amount' => 185000],
                    ['source' => 'Short course fees — digital literacy & Office productivity', 'amount' => 220000],
                    ['source' => 'Internal service recharge — campus Wi‑Fi & printing', 'amount' => 310000],
                    ['source' => 'Partner contribution — student information systems uplift', 'amount' => 450000],
                ],
                'expenditure' => [
                    ['item' => 'Campus internet backbone & ISP primary link', 'quantity' => 3, 'description' => 'Q1 contracted bandwidth (monthly)', 'unit_price' => 185000, 'unit_of_measure' => 'month'],
                    ['item' => 'Firewall / UTM licence renewal (core site)', 'quantity' => 1, 'description' => 'Threat prevention, VPN, URL filtering', 'unit_price' => 480000, 'unit_of_measure' => 'year-share'],
                    ['item' => 'Helpdesk ticketing & remote support platform', 'quantity' => 1, 'description' => 'Annual subscription (Q1 activation)', 'unit_price' => 165000, 'unit_of_measure' => 'lot'],
                    ['item' => 'Endpoint antivirus / EDR seats', 'quantity' => 250, 'description' => 'Staff and lab endpoints', 'unit_price' => 1200, 'unit_of_measure' => 'seat'],
                    ['item' => 'Server room UPS batteries & PDU service', 'quantity' => 1, 'description' => 'Preventive maintenance and replacements', 'unit_price' => 275000, 'unit_of_measure' => 'lot'],
                    ['item' => 'Structured cabling remediation (priority blocks)', 'quantity' => 4, 'description' => 'Patch panels, drops, labelling', 'unit_price' => 95000, 'unit_of_measure' => 'block'],
                    ['item' => 'ICT staff technical certification vouchers', 'quantity' => 6, 'description' => 'Networking / cloud / security exams', 'unit_price' => 28000, 'unit_of_measure' => 'voucher'],
                    ['item' => 'Spare parts pool — switches, NICs, SSDs', 'quantity' => 1, 'description' => 'Critical spares for Q1 incidents', 'unit_price' => 210000, 'unit_of_measure' => 'lot'],
                ],
            ],
            'q2' => [
                'income' => [
                    ['source' => 'Institutional ICT operations allocation', 'amount' => 2100000],
                    ['source' => 'Computer lab booking & exam-centre ICT support fees', 'amount' => 265000],
                    ['source' => 'Microsoft / productivity licensing cost-recovery (departments)', 'amount' => 390000],
                    ['source' => 'Short course fees — cybersecurity awareness for staff', 'amount' => 175000],
                    ['source' => 'Website & digital marketing hosting recharge', 'amount' => 140000],
                ],
                'expenditure' => [
                    ['item' => 'Campus internet backbone & ISP primary link', 'quantity' => 3, 'description' => 'Q2 contracted bandwidth', 'unit_price' => 185000, 'unit_of_measure' => 'month'],
                    ['item' => 'Microsoft 365 Education / productivity licences', 'quantity' => 1, 'description' => 'Faculty, staff, and shared mailboxes', 'unit_price' => 620000, 'unit_of_measure' => 'lot'],
                    ['item' => 'Learning / ERP application hosting (cloud VMs)', 'quantity' => 3, 'description' => 'Compute, storage, snapshots', 'unit_price' => 145000, 'unit_of_measure' => 'month'],
                    ['item' => 'Identity & MFA platform (staff SSO)', 'quantity' => 1, 'description' => 'Directory sync and conditional access', 'unit_price' => 230000, 'unit_of_measure' => 'lot'],
                    ['item' => 'Computer lab refresh — teaching PCs (phase 1)', 'quantity' => 25, 'description' => 'Desktops for Lab A', 'unit_price' => 78000, 'unit_of_measure' => 'unit'],
                    ['item' => 'Classroom AV & interactive display maintenance', 'quantity' => 12, 'description' => 'Projectors, boards, cables', 'unit_price' => 18500, 'unit_of_measure' => 'room'],
                    ['item' => 'Penetration test & vulnerability assessment', 'quantity' => 1, 'description' => 'External assessment of public services', 'unit_price' => 350000, 'unit_of_measure' => 'engagement'],
                    ['item' => 'On-call contractor support (peak exam season)', 'quantity' => 6, 'description' => 'After-hours coverage weeks', 'unit_price' => 45000, 'unit_of_measure' => 'week'],
                ],
            ],
            'q3' => [
                'income' => [
                    ['source' => 'Institutional ICT operations allocation', 'amount' => 2100000],
                    ['source' => 'Partner contribution — campus network expansion', 'amount' => 680000],
                    ['source' => 'ICT consultancy — LAN redesign for satellite site', 'amount' => 420000],
                    ['source' => 'Short course fees — systems administration fundamentals', 'amount' => 195000],
                    ['source' => 'Printing & ID card production service fees', 'amount' => 155000],
                ],
                'expenditure' => [
                    ['item' => 'Campus internet backbone & ISP primary + failover', 'quantity' => 3, 'description' => 'Primary link plus Q3 failover retainer', 'unit_price' => 210000, 'unit_of_measure' => 'month'],
                    ['item' => 'Core switch / access switch upgrade (phase 2)', 'quantity' => 6, 'description' => 'PoE access switches for new wings', 'unit_price' => 185000, 'unit_of_measure' => 'unit'],
                    ['item' => 'Wireless access points & controller licences', 'quantity' => 40, 'description' => 'Indoor APs for hostels and lecture blocks', 'unit_price' => 32000, 'unit_of_measure' => 'AP'],
                    ['item' => 'Backup & disaster-recovery storage expansion', 'quantity' => 1, 'description' => 'On-prem NAS + offsite replication', 'unit_price' => 540000, 'unit_of_measure' => 'lot'],
                    ['item' => 'CCTV / access-control network segmentation', 'quantity' => 1, 'description' => 'VLAN, cameras uplink, NVR storage', 'unit_price' => 390000, 'unit_of_measure' => 'lot'],
                    ['item' => 'Software development / integration sprints', 'quantity' => 2, 'description' => 'SIS–finance–HR connectors and bugfix', 'unit_price' => 275000, 'unit_of_measure' => 'sprint'],
                    ['item' => 'User training — new staff digital onboarding', 'quantity' => 4, 'description' => 'Facilitation and lab time', 'unit_price' => 55000, 'unit_of_measure' => 'cohort'],
                    ['item' => 'Consumables — toner, cables, racks accessories', 'quantity' => 1, 'description' => 'Operations stock for Q3', 'unit_price' => 145000, 'unit_of_measure' => 'lot'],
                ],
            ],
            'q4' => [
                'income' => [
                    ['source' => 'Institutional ICT operations allocation', 'amount' => 2100000],
                    ['source' => 'Year-end licence cost-recovery from academic units', 'amount' => 480000],
                    ['source' => 'External training contract — county digital skills cohort', 'amount' => 560000],
                    ['source' => 'Asset disposal proceeds (approved write-offs)', 'amount' => 95000],
                    ['source' => 'Holiday short course fees — web & multimedia basics', 'amount' => 210000],
                ],
                'expenditure' => [
                    ['item' => 'Campus internet backbone & ISP primary link', 'quantity' => 3, 'description' => 'Q4 contracted bandwidth', 'unit_price' => 185000, 'unit_of_measure' => 'month'],
                    ['item' => 'Annual domain, SSL, DNS & email security renewals', 'quantity' => 1, 'description' => 'Institutional domains and anti-phishing', 'unit_price' => 195000, 'unit_of_measure' => 'lot'],
                    ['item' => 'Laptop refresh — critical staff fleet (phase 2)', 'quantity' => 18, 'description' => 'Standard corporate images and docking', 'unit_price' => 95000, 'unit_of_measure' => 'unit'],
                    ['item' => 'Data centre cooling & environmental monitoring', 'quantity' => 1, 'description' => 'AC service, sensors, filters', 'unit_price' => 260000, 'unit_of_measure' => 'lot'],
                    ['item' => 'Business continuity drill & runbook update', 'quantity' => 1, 'description' => 'Failover test, documentation, tabletop', 'unit_price' => 175000, 'unit_of_measure' => 'engagement'],
                    ['item' => 'Licence true-up & software asset management audit', 'quantity' => 1, 'description' => 'Inventory, compliance, renewals calendar', 'unit_price' => 220000, 'unit_of_measure' => 'lot'],
                    ['item' => 'ICT annual performance & roadmap workshop', 'quantity' => 1, 'description' => 'FY2028 architecture and budget pipeline', 'unit_price' => 185000, 'unit_of_measure' => 'event'],
                    ['item' => 'Contingency — critical incident response retainer', 'quantity' => 1, 'description' => 'Emergency vendor hours and parts', 'unit_price' => 300000, 'unit_of_measure' => 'lot'],
                ],
            ],
        ];

        [$payload, $total] = $this->buildAnnualPayload($quarters);

        return [
            'planning_cycle_id' => $cycleId,
            'department_id' => $departmentId,
            'title' => 'FY2027 ICT Department Annual Budget',
            'framework' => 'standard',
            'budget_type' => 'annual',
            'requested_amount' => $total,
            'standard_line_items' => $payload,
            'justification' => "Annual ICT Department financial plan for fiscal year 2027 under the 2027 Financial Budgeting cycle.\n\n"
               ."Priorities: (1) reliable campus connectivity and classroom technology; (2) cybersecurity and identity controls; "
               ."(3) application hosting for ERP/SIS workloads; (4) phased lab and staff device refresh; "
               ."(5) backup, disaster recovery, and year-end licence compliance.\n\n"
               .'Income combines the institutional ICT allocation with service recharges, short courses, and partner contributions. '
                .'Expenditure is sequenced across the year—core licences and resilience (Q1), productivity platforms and exam-season support (Q2), network expansion and DR capacity (Q3), and refresh plus continuity assurance (Q4).',
        ];
    }

    /**
     * @param  array<string, array{income: list<array{source: string, amount: float|int}>, expenditure: list<array{item: string, quantity: float|int, description: string, unit_price: float|int, unit_of_measure: string}>}>  $quarters
     * @return array{0: array<string, mixed>, 1: float}
     */
    private function buildAnnualPayload(array $quarters): array
    {
        $payloadQuarters = [];
        $flatLines = [];
        $incomeGrand = 0.0;
        $expenditureGrand = 0.0;

        foreach (array_keys(self::QUARTER_LABELS) as $key) {
            $block = $quarters[$key];
            $incomeRows = [];
            $incomeTotal = 0.0;
            foreach ($block['income'] as $row) {
                $amount = round((float) $row['amount'], 2);
                $incomeRows[] = [
                    'source' => $row['source'],
                    'amount' => $amount,
                ];
                $incomeTotal += $amount;
            }

            $expenditureRows = [];
            $expenditureTotal = 0.0;
            foreach ($block['expenditure'] as $row) {
                $quantity = round((float) $row['quantity'], 4);
                $unitPrice = round((float) $row['unit_price'], 2);
                $total = round($quantity * $unitPrice, 2);
                $normalized = [
                    'item' => $row['item'],
                    'quantity' => $quantity,
                    'description' => $row['description'],
                    'unit_price' => $unitPrice,
                    'unit_of_measure' => $row['unit_of_measure'],
                    'total' => $total,
                    'quarter' => $key,
                ];
                $expenditureRows[] = $normalized;
                $flatLines[] = $normalized;
                $expenditureTotal += $total;
            }

            $payloadQuarters[$key] = [
                'income' => $incomeRows,
                'income_total' => round($incomeTotal, 2),
                'expenditure' => $expenditureRows,
                'expenditure_total' => round($expenditureTotal, 2),
            ];
            $incomeGrand += $incomeTotal;
            $expenditureGrand += $expenditureTotal;
        }

        return [[
            'format' => 'annual_quarters_v1',
            'quarters' => $payloadQuarters,
            'income_grand_total' => round($incomeGrand, 2),
            'expenditure_grand_total' => round($expenditureGrand, 2),
            'lines' => $flatLines,
        ], round($expenditureGrand, 2)];
    }
}
