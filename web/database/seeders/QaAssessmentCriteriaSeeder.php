<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Qa\QaPlan;
use App\Models\Staff;
use App\Models\User;
use App\Services\Qa\QaAssessmentService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class QaAssessmentCriteriaSeeder extends Seeder
{
    public const PLAN_NAME = 'Institutional Quality Assessment Sheet (Seeded Criteria)';

    public function run(): void
    {
        if (! Schema::hasTable('qa_plans') || ! Schema::hasTable('qa_audit_checklists')) {
            $this->command?->warn('QA tables missing - skipped QaAssessmentCriteriaSeeder.');

            return;
        }

        $user = $this->resolveQaUser();
        if (! $user) {
            $this->command?->warn('No staff user available for QA seeding (run QaDemoSeeder first).');

            return;
        }

        $departments = Department::query()
            ->active()
            ->orderBy('dept_name')
            ->get();

        if ($departments->isEmpty()) {
            $this->command?->warn('No active departments found - skipped QA criteria seeder.');

            return;
        }

        $qa = app(QaAssessmentService::class);
        $departmentIds = $departments->pluck('id')->map(fn ($id) => (int) $id)->all();

        if (! QaPlan::query()->where('plan_name', self::PLAN_NAME)->exists()) {
            $plan = $qa->createPlan(
                $user,
                [
                    'plan_name' => self::PLAN_NAME,
                    'description' => 'Comprehensive institutional quality assessment covering governance, teaching and learning, student support, clinical/practical delivery, records, and continuous improvement. Seeded for all active departments.',
                    'instructions' => $this->scoringInstructions(),
                    'period_start' => now()->startOfYear()->toDateString(),
                    'period_end' => now()->endOfYear()->toDateString(),
                    'due_at' => now()->addMonths(2)->setTime(17, 0)->format('Y-m-d H:i:s'),
                    'pass_threshold' => 70,
                ],
                $this->institutionalCriteria(),
                $departmentIds,
            );

            $this->command?->info(sprintf(
                'Created institutional QA plan #%d with %d criteria for %d departments.',
                $plan->id,
                $plan->checklists->count(),
                count($departmentIds),
            ));
        } else {
            $this->command?->info('Institutional QA assessment plan already exists - skipped.');
        }

        foreach ($departments as $department) {
            $planName = $this->departmentPlanName($department);
            if (QaPlan::query()->where('plan_name', $planName)->exists()) {
                $this->command?->info("Department sheet for {$department->dept_code} already exists - skipped.");

                continue;
            }

            $items = $this->criteriaForDepartment($department);
            if ($items === []) {
                continue;
            }

            $plan = $qa->createPlan(
                $user,
                [
                    'plan_name' => $planName,
                    'description' => "Department-specific quality assessment for {$department->dept_name} ({$department->dept_code}). Criteria are tailored to this unit’s mandate.",
                    'instructions' => $this->scoringInstructions()
                        ."\n\nThis sheet applies only to {$department->dept_name}. Respond with evidence from this department’s operations.",
                    'period_start' => now()->startOfYear()->toDateString(),
                    'period_end' => now()->endOfYear()->toDateString(),
                    'due_at' => now()->addMonths(2)->setTime(17, 0)->format('Y-m-d H:i:s'),
                    'pass_threshold' => 70,
                ],
                $items,
                [(int) $department->id],
            );

            $this->command?->info(sprintf(
                'Created department QA plan #%d for %s with %d criteria.',
                $plan->id,
                $department->dept_code,
                $plan->checklists->count(),
            ));
        }
    }

    public static function departmentPlanName(Department $department): string
    {
        return "Department Quality Sheet - {$department->dept_name} ({$department->dept_code}) [Seeded]";
    }

    private function scoringInstructions(): string
    {
        return "Complete every criterion honestly and attach supporting evidence where required.\n\n"
            ."Scoring guide:\n"
            ."- 90-100: Fully meets / exceeds the standard with current evidence\n"
            ."- 70-89: Largely meets the standard; minor gaps\n"
            ."- 50-69: Partially meets; clear improvement actions needed\n"
            ."- Below 50: Does not meet the standard; corrective action required\n\n"
            .'Use comments to note root causes, owners, and timelines for any shortfalls.';
    }

    private function resolveQaUser(): ?User
    {
        $staff = Staff::query()
            ->where('employee_number', 'EMP-QA-001')
            ->orWhere('job_title', 'like', '%Quality%')
            ->orderBy('id')
            ->first();

        if ($staff?->user_id) {
            $user = User::query()->find($staff->user_id);
            if ($user) {
                if (! $user->staff_id) {
                    $user->update(['staff_id' => $staff->id]);
                }

                return $user->fresh();
            }
        }

        return User::query()
            ->whereNotNull('staff_id')
            ->where('is_active', 1)
            ->orderBy('id')
            ->first();
    }

    /**
     * @return list<array{text: string, category: string, weight: float|int, max_score: int, requires_evidence: bool}>
     */
    private function institutionalCriteria(): array
    {
        return $this->mapCriteria([
            ['Governance & leadership', 1.5, true, 'The department has a current, approved annual work plan aligned to institutional strategic priorities and quality objectives.'],
            ['Governance & leadership', 1.5, true, 'Roles, responsibilities, and reporting lines within the department are documented, communicated, and understood by all staff.'],
            ['Governance & leadership', 1.25, true, 'Departmental meetings are held regularly, minuted, actioned, and tracked to closure (including quality and compliance agenda items).'],
            ['Governance & leadership', 1.25, true, 'There is evidence of HOD/line-manager oversight of teaching, assessment, and service delivery standards within the department.'],
            ['Governance & leadership', 1.0, true, 'Risks affecting quality (academic integrity, staffing, equipment, clinical sites, data) are identified, logged, and reviewed at least quarterly.'],
            ['Policy & compliance', 1.5, true, 'Staff can access and demonstrate awareness of current institutional policies relevant to the department (academic, HR, finance, ICT, safeguarding).'],
            ['Policy & compliance', 1.5, true, 'Regulatory and professional body requirements applicable to the department’s programmes/services are mapped and monitored (e.g. TVETA, nursing council, ministry circulars).'],
            ['Policy & compliance', 1.25, true, 'Internal audit / QA findings from the previous cycle have documented corrective actions with owners, deadlines, and verification of closure.'],
            ['Policy & compliance', 1.0, true, 'Data protection and confidentiality requirements are applied to student, staff, and patient/client records handled by the department.'],
            ['Teaching & learning', 1.75, true, 'Approved curricula / scheme of work / lesson plans are available for active units and are followed in day-to-day delivery.'],
            ['Teaching & learning', 1.5, true, 'Learning outcomes for each unit are clearly stated, shared with learners at the start of the unit, and assessed appropriately.'],
            ['Teaching & learning', 1.5, true, 'Teaching methods are varied and appropriate to the subject (theory, skills lab, clinical/practical, tutorials) and learner level.'],
            ['Teaching & learning', 1.25, true, 'Attendance is taken consistently, analysed for at-risk learners, and followed up according to institutional rules.'],
            ['Teaching & learning', 1.25, true, 'Learning resources (handouts, LMS content, reading lists, skills checklists) are current, accurate, and accessible to registered learners.'],
            ['Teaching & learning', 1.0, false, 'Class sizes and contact hours are manageable relative to allocated staffing and room/lab capacity.'],
            ['Assessment & examinations', 1.75, true, 'Assessment blueprints / tables of specification align assessment tasks to learning outcomes and approved weighting.'],
            ['Assessment & examinations', 1.5, true, 'Continuous assessment and examination scripts/tools are moderated before use, with records of moderation retained.'],
            ['Assessment & examinations', 1.5, true, 'Marking is fair, consistent, and timely; sampled scripts show use of marking guides/rubrics and second marking where required.'],
            ['Assessment & examinations', 1.5, true, 'Examination security (storage, printing, invigilation, collection) follows institutional SOPs with no unresolved breaches this period.'],
            ['Assessment & examinations', 1.25, true, 'Results are verified, approved through the correct boards/committees, and released to learners within published timelines.'],
            ['Assessment & examinations', 1.25, true, 'Supplementary, special, and deferred exam processes are documented, transparent, and applied consistently.'],
            ['Clinical & practical training', 1.75, true, 'Clinical/practical placement sites are approved, MoU/agreements are current, and site suitability is reviewed at least annually.'],
            ['Clinical & practical training', 1.5, true, 'Learners receive orientation, learning objectives, and supervision schedules before and during placements.'],
            ['Clinical & practical training', 1.5, true, 'Clinical instructors / preceptors are oriented to assessment tools and complete competency assessments as required.'],
            ['Clinical & practical training', 1.25, true, 'Skills laboratory equipment, consumables, and simulation scenarios are adequate, safe, and maintained for the enrolled cohort.'],
            ['Clinical & practical training', 1.25, true, 'Infection prevention, patient safety, and professional conduct standards are enforced and evidenced during practical training.'],
            ['Student support & welfare', 1.25, true, 'Academic advising / mentorship arrangements are in place; at-risk students are identified and supported with documented interventions.'],
            ['Student support & welfare', 1.0, true, 'Student complaints and appeals related to the department are logged, investigated, and resolved within policy timelines.'],
            ['Student support & welfare', 1.0, true, 'Reasonable accommodations for learners with disability or special needs are considered and implemented where approved.'],
            ['Student support & welfare', 1.0, false, 'Communication channels to students (timetable changes, assessments, fees-related academic holds) are timely and traceable.'],
            ['Staffing & capacity', 1.5, true, 'Staff establishment versus workload is reviewed; critical gaps (unfilled posts, overloads) are escalated with interim mitigation.'],
            ['Staffing & capacity', 1.25, true, 'Recruitment, induction, and probation records for departmental staff are complete and current.'],
            ['Staffing & capacity', 1.25, true, 'Continuous professional development / capacity-building participation is planned, recorded, and linked to performance needs.'],
            ['Staffing & capacity', 1.0, true, 'Staff performance appraisal / peer observation of teaching (where applicable) is conducted and used for improvement.'],
            ['Staffing & capacity', 1.0, true, 'Conflict of interest, dual employment, and moonlighting declarations relevant to teaching/assessment are managed per policy.'],
            ['Records & information management', 1.5, true, 'Student academic records (registration, CATs, exam marks, progression) are accurate, complete, and backed up according to ICT/registry SOPs.'],
            ['Records & information management', 1.25, true, 'Departmental filing (physical and/or digital) follows a clear retention schedule; obsolete records are disposed of securely.'],
            ['Records & information management', 1.25, true, 'ERP/portal data entry for the department is timely and quality-checked (enrolment, grades, attendance, requests).'],
            ['Records & information management', 1.0, true, 'Audit trails exist for amendments to marks, attendance, or clearance decisions, with authorisation recorded.'],
            ['Resources, facilities & safety', 1.25, true, 'Teaching rooms, labs, and offices used by the department are clean, accessible, and fit for purpose.'],
            ['Resources, facilities & safety', 1.25, true, 'Equipment inventory is maintained; defective items are reported, quarantined, and repaired/replaced promptly.'],
            ['Resources, facilities & safety', 1.0, true, 'Occupational health and safety, fire, and emergency procedures are known to staff and practised where required.'],
            ['Resources, facilities & safety', 1.0, false, 'Budget requests and utilisation for teaching materials / capacity activities are tracked against approved plans.'],
            ['Feedback & continuous improvement', 1.5, true, 'Course and lecturer evaluation results are reviewed each cycle; improvement actions are documented and followed up.'],
            ['Feedback & continuous improvement', 1.25, true, 'Employer / clinical site / graduate feedback (where available) informs curriculum or service improvements.'],
            ['Feedback & continuous improvement', 1.25, true, 'The department maintains a quality improvement log (PDCA or equivalent) with measurable outcomes for priority gaps.'],
            ['Feedback & continuous improvement', 1.0, true, 'Best practices and lessons learned are shared across related departments or programme teams at least once per semester.'],
            ['Integrity & ethics', 1.5, true, 'Academic integrity cases (plagiarism, exam malpractice, falsification) are handled per policy with consistent sanctions and records.'],
            ['Integrity & ethics', 1.25, true, 'Research / project supervision (if applicable) follows ethics approval and authorship/attribution standards.'],
            ['Integrity & ethics', 1.0, true, 'Staff and student codes of conduct are enforced; disciplinary processes are fair, confidential, and documented.'],
        ]);
    }

    /**
     * @return list<array{text: string, category: string, weight: float|int, max_score: int, requires_evidence: bool}>
     */
    private function criteriaForDepartment(Department $department): array
    {
        $code = strtoupper((string) $department->dept_code);
        $category = strtolower((string) ($department->dept_category ?? ''));

        $specific = match ($code) {
            'ACAD' => $this->academicsCriteria(),
            'ADM' => $this->adminCriteria(),
            'FIN' => $this->financeCriteria(),
            'HR' => $this->hrCriteria(),
            'ICTO', 'ICT' => $this->ictCriteria($code === 'ICT'),
            'PRC' => $this->procurementCriteria(),
            'QA' => $this->qaOfficeCriteria(),
            'RES' => $this->researchCriteria(),
            'MKT' => $this->marketingCriteria(),
            'M&E', 'MNE', 'ME' => $this->meCriteria(),
            'CHS' => $this->healthSciencesCriteria(),
            'BUS' => $this->businessCriteria(),
            'HOS' => $this->hospitalityCriteria(),
            'TEC' => $this->technicalCriteria(),
            default => $category === 'academic'
                ? $this->genericAcademicCriteria($department->dept_name)
                : $this->genericAdminCriteria($department->dept_name),
        };

        return $this->mapCriteria($specific);
    }

    /** @return list<array{0: string, 1: float|int, 2: bool, 3: string}> */
    private function academicsCriteria(): array
    {
        return [
            ['Academic governance', 1.75, true, 'Senate/Academic Board decisions are implemented and tracked within published timelines, with evidence of follow-up.'],
            ['Academic governance', 1.5, true, 'Programme approval, review, and curriculum change workflows are documented and complied with for all active programmes.'],
            ['Admissions & enrolment', 1.5, true, 'Admission criteria, selection records, and enrolment lists are complete, auditable, and reconciled to the student information system.'],
            ['Admissions & enrolment', 1.25, true, 'Credit transfer, exemptions, and prior learning recognition decisions are evidenced and approved by the competent authority.'],
            ['Timetabling & delivery', 1.5, true, 'Master and programme timetables are published on time, conflict-checked, and updated when changes occur with learner notification.'],
            ['Timetabling & delivery', 1.25, true, 'Unit registration and semester progression rules are enforced consistently across departments.'],
            ['Examinations office', 1.75, true, 'Examination schedules, venues, invigilation rosters, and incident logs are complete for the current assessment cycle.'],
            ['Examinations office', 1.5, true, 'External examiner / moderator engagement (where required) is planned, documented, and reflected in result processing.'],
            ['Student records', 1.5, true, 'Transcripts, certificates, and academic documents issued by the registry are accurate and protected against unauthorised alteration.'],
            ['Quality liaison', 1.25, true, 'Academics coordinates with QA on programme self-evaluation, tracer studies, and regulatory inspection readiness.'],
            ['Clearance & graduation', 1.25, true, 'Graduation lists and clearance workflows (academic component) are verified before certification.'],
            ['Stakeholder communication', 1.0, true, 'Circulars and academic calendars are version-controlled and accessible to HODs, tutors, and students.'],
        ];
    }

    /** @return list<array{0: string, 1: float|int, 2: bool, 3: string}> */
    private function adminCriteria(): array
    {
        return [
            ['Office administration', 1.5, true, 'Incoming/outgoing correspondence is logged, routed, and filed with clear ownership and response timelines.'],
            ['Office administration', 1.25, true, 'Meeting logistics for management committees are organised; agendas and minutes are distributed promptly.'],
            ['Facilities coordination', 1.5, true, 'Room booking, facilities requests, and estate issues affecting teaching are tracked to resolution.'],
            ['Transport & logistics', 1.25, true, 'Official transport / courier arrangements for exams, placements, or events are authorised and recorded.'],
            ['Front office / reception', 1.25, true, 'Visitor and enquiry handling standards are defined; sensitive student/staff information is not disclosed inappropriately.'],
            ['Records centre', 1.5, true, 'Central filing and archive retrieval meet retention and confidentiality requirements.'],
            ['Service charters', 1.0, true, 'Administrative service standards (turnaround times) are published and monitored.'],
            ['Asset care', 1.0, true, 'Office equipment and consumables under Admin custody are inventoried and safeguarded.'],
            ['Compliance support', 1.25, true, 'Institutional seals, letterheads, and official stamps are controlled and issued only under authorisation.'],
            ['Emergency readiness', 1.0, true, 'Admin supports fire drills, emergency contacts lists, and first-aid point readiness on campus.'],
        ];
    }

    /** @return list<array{0: string, 1: float|int, 2: bool, 3: string}> */
    private function financeCriteria(): array
    {
        return [
            ['Fee management', 1.75, true, 'Student fee structures, invoices, and receipts reconcile to the student finance ledger without unexplained variances.'],
            ['Fee management', 1.5, true, 'Payment plans, waivers, and bursaries are approved under policy and reflected accurately in student accounts.'],
            ['Collections & clearance', 1.5, true, 'Financial clearance decisions for exams, results, and graduation follow published thresholds and audit trails.'],
            ['Budgeting', 1.5, true, 'Departmental budgets are issued, monitored, and variance reports shared with budget holders at least quarterly.'],
            ['Expenditure control', 1.5, true, 'Payment vouchers have complete supporting documents (LPO, GRN, invoice, approvals) before disbursement.'],
            ['Cash & bank', 1.75, true, 'Bank reconciliations are prepared monthly, reviewed, and unresolved items aged and explained.'],
            ['Payroll interface', 1.25, true, 'Payroll inputs from HR are validated; statutory deductions (PAYE, NSSF, SHIF/SHA, etc.) are remitted on time.'],
            ['Procurement payments', 1.25, true, 'Supplier payments follow contract terms; duplicates and ghost invoices are prevented by system/controls.'],
            ['Reporting', 1.25, true, 'Management accounts / financial reports for the institution are timely, accurate, and reviewed by authorised officers.'],
            ['Internal control', 1.5, true, 'Segregation of duties exists between receipting, posting, and reconciliation functions.'],
            ['Student communication', 1.0, true, 'Fee statements and arrears notices are accessible to students and free from systematic errors.'],
            ['Audit readiness', 1.25, true, 'Prior external/internal audit finance findings have closed management actions or an approved remediation plan.'],
        ];
    }

    /** @return list<array{0: string, 1: float|int, 2: bool, 3: string}> */
    private function hrCriteria(): array
    {
        return [
            ['Workforce planning', 1.5, true, 'Establishment vs filled posts is maintained; recruitment plans address critical teaching and support gaps.'],
            ['Recruitment', 1.5, true, 'Recruitment files show advert, shortlisting, interview, and appointment decisions consistent with HR policy.'],
            ['Onboarding', 1.25, true, 'New staff complete induction, contract signing, and mandatory document submission within probation timelines.'],
            ['Contracts & records', 1.5, true, 'Personnel files (contracts, licences, next of kin, tax PINs) are complete, secure, and up to date.'],
            ['Leave & attendance', 1.25, true, 'Leave balances and attendance/absence records are accurate and approved before payroll cut-off.'],
            ['Performance management', 1.25, true, 'Appraisal cycles are scheduled; incomplete appraisals are escalated; development plans are documented.'],
            ['Discipline & grievance', 1.5, true, 'Disciplinary and grievance cases follow due process with confidential, complete case files.'],
            ['Staff welfare', 1.0, true, 'Welfare, medical, and occupational issues raised by staff are logged and addressed under policy.'],
            ['Training coordination', 1.25, true, 'Institutional CPD calendar and nominations are coordinated with departments and QA capacity needs.'],
            ['Compliance', 1.25, true, 'Statutory HR returns and labour compliance obligations are met within legal deadlines.'],
            ['Separation', 1.0, true, 'Exit clearances, final dues, and handover notes are completed before staff release.'],
        ];
    }

    /** @return list<array{0: string, 1: float|int, 2: bool, 3: string}> */
    private function ictCriteria(bool $academicUnit): array
    {
        $rows = [
            ['Service availability', 1.75, true, 'Core systems (ERP, email, LMS, student portal) have uptime targets, monitoring, and documented incident response.'],
            ['Access control', 1.75, true, 'User provisioning and de-provisioning follow joiner-mover-leaver processes; privileged access is reviewed periodically.'],
            ['Data backup', 1.75, true, 'Backups are scheduled, tested for restore, and stored securely off primary production where feasible.'],
            ['Cybersecurity', 1.5, true, 'Endpoint protection, patching, and phishing awareness measures are in place; critical vulnerabilities are tracked to closure.'],
            ['Change management', 1.25, true, 'System changes and deployments are approved, tested, and rolled back if needed, with change records retained.'],
            ['Helpdesk', 1.25, true, 'ICT helpdesk tickets are logged, prioritised, and resolved within agreed service levels.'],
            ['Network & labs', 1.25, true, 'Campus network, Wi-Fi coverage for teaching spaces, and computer lab readiness are maintained for peak class loads.'],
            ['Asset management', 1.0, true, 'ICT assets are tagged, inventoried, and disposed of securely at end of life.'],
            ['Policy compliance', 1.25, true, 'Acceptable use, password, and data classification policies are enforced and communicated.'],
        ];

        if ($academicUnit) {
            $rows = array_merge($rows, [
                ['Programme delivery', 1.5, true, 'ICT programme units have current lab practicals, software licences, and assessment tools aligned to the curriculum.'],
                ['Student projects', 1.25, true, 'Capstone / project supervision guidelines, plagiarism checks, and presentation schedules are documented and followed.'],
                ['Industry linkage', 1.0, true, 'Industry attachments or guest sessions for ICT learners are planned and evaluated.'],
            ]);
        } else {
            $rows = array_merge($rows, [
                ['Business continuity', 1.25, true, 'ICT disaster recovery / business continuity arrangements for examinations and finance systems are documented and rehearsed.'],
                ['Integration quality', 1.25, true, 'Integrations between modules (finance, academics, HR) are monitored for failed jobs and data mismatches.'],
            ]);
        }

        return $rows;
    }

    /** @return list<array{0: string, 1: float|int, 2: bool, 3: string}> */
    private function procurementCriteria(): array
    {
        return [
            ['Procurement planning', 1.5, true, 'Annual procurement plans align to approved budgets and are updated when priorities change.'],
            ['Requisition control', 1.5, true, 'User department requisitions are complete, authorised, and within available budget before sourcing.'],
            ['Sourcing & competition', 1.75, true, 'Quotations/tenders meet threshold rules; evaluation committees and score sheets are retained.'],
            ['Contracting', 1.5, true, 'LPOs/contracts clearly state specifications, quantities, delivery dates, and penalty clauses where applicable.'],
            ['Receiving & inspection', 1.5, true, 'Goods/services are inspected against specifications; GRNs and rejection notes are filed.'],
            ['Stores & inventory', 1.5, true, 'Stock cards / system balances match physical counts; discrepancies are investigated.'],
            ['Supplier management', 1.25, true, 'Supplier performance (quality, timeliness) is monitored; poor performers are flagged.'],
            ['Ethics', 1.75, true, 'Conflict of interest declarations for procurement officers and evaluators are on file for active awards.'],
            ['Asset tagging', 1.0, true, 'Capital items are tagged and handed to asset custodians with documentation.'],
            ['Audit trail', 1.25, true, 'End-to-end procurement files can be reconstructed for any sampled transaction within the period.'],
        ];
    }

    /** @return list<array{0: string, 1: float|int, 2: bool, 3: string}> */
    private function qaOfficeCriteria(): array
    {
        return [
            ['QA programme management', 1.75, true, 'The annual QA calendar (assessments, capacity, audits, reviews) is approved and progress is tracked.'],
            ['Assessment design', 1.5, true, 'Assessment sheets are criterion-based, mapped to standards, and piloted/reviewed before institution-wide use.'],
            ['Dispatch & follow-up', 1.5, true, 'Dispatched assessments are monitored for completion; late departments are escalated systematically.'],
            ['Evidence review', 1.5, true, 'Submitted evidence is sampled for authenticity and relevance; weak evidence is returned with guidance.'],
            ['Scoring integrity', 1.5, true, 'Compliance scoring methods are documented; recalculations and overrides are authorised and logged.'],
            ['Corrective actions', 1.75, true, 'Corrective action plans from non-compliant areas have owners, deadlines, and verification of effectiveness.'],
            ['Capacity building', 1.25, true, 'QA capacity sessions address identified gaps and attendance/evaluation records are retained.'],
            ['Regulatory readiness', 1.5, true, 'Inspection files (self-evaluations, evidence binders, previous recommendations) are current and organised.'],
            ['Stakeholder reporting', 1.25, true, 'QA reports to management/board summarise risks, trends, and improvement status without material omissions.'],
            ['Independence', 1.0, true, 'QA maintains functional independence in reporting findings; conflicts are declared when assessing own unit processes.'],
            ['Knowledge management', 1.0, true, 'Standards, templates, and past assessment tools are version-controlled and accessible to authorised users.'],
        ];
    }

    /** @return list<array{0: string, 1: float|int, 2: bool, 3: string}> */
    private function researchCriteria(): array
    {
        return [
            ['Research governance', 1.5, true, 'Research policy and ethics review procedures are published and followed for student/staff projects.'],
            ['Ethics compliance', 1.75, true, 'Ethics approvals (and renewals) are on file before data collection involving human subjects.'],
            ['Proposal quality', 1.25, true, 'Proposal review turnaround times meet service standards; feedback to researchers is documented.'],
            ['Supervision', 1.5, true, 'Research supervisors are allocated with load monitoring; progress meetings are recorded for active candidates.'],
            ['Integrity', 1.5, true, 'Plagiarism checking and research misconduct procedures are applied consistently.'],
            ['Publication & dissemination', 1.0, true, 'Institutional repository / publication records of staff and student outputs are maintained.'],
            ['Grants & partnerships', 1.25, true, 'Grant applications and MoUs for research collaboration are tracked with compliance obligations listed.'],
            ['Capacity building', 1.0, true, 'Research methodology workshops are planned and evaluated for relevance.'],
            ['Data management', 1.25, true, 'Research data storage and retention follow ethics and data protection commitments.'],
            ['IP & commercialisation', 1.0, true, 'Intellectual property disclosures (where applicable) follow institutional IP procedures.'],
        ];
    }

    /** @return list<array{0: string, 1: float|int, 2: bool, 3: string}> */
    private function marketingCriteria(): array
    {
        return [
            ['Brand & messaging', 1.25, true, 'Public marketing materials accurately represent programmes, fees, and accreditation status.'],
            ['Lead management', 1.5, true, 'Enquiry-to-application funnels are tracked; response SLAs to prospective students are met.'],
            ['Campaign planning', 1.25, true, 'Campaign plans have budgets, targets, and post-campaign evaluation reports.'],
            ['Digital presence', 1.25, true, 'Website and social content are current; outdated programme information is corrected promptly.'],
            ['Events', 1.0, true, 'Open days / career fairs are planned with risk assessments and follow-up conversion tracking.'],
            ['Reputation risk', 1.5, true, 'Public complaints and media issues are escalated under a documented communications protocol.'],
            ['Compliance', 1.25, true, 'Advertising complies with consumer protection and education advertising guidelines.'],
            ['Internal coordination', 1.0, true, 'Marketing coordinates programme facts with Academics and Admissions before publication.'],
            ['CRM / records', 1.0, true, 'Prospect and alumni contact data are maintained with consent and privacy controls.'],
        ];
    }

    /** @return list<array{0: string, 1: float|int, 2: bool, 3: string}> */
    private function meCriteria(): array
    {
        return [
            ['Results framework', 1.75, true, 'Institutional / departmental results frameworks (outputs, outcomes, indicators) are current and approved.'],
            ['Data quality', 1.5, true, 'Indicator definitions, data sources, and collection frequencies are documented and followed.'],
            ['Reporting cycle', 1.5, true, 'Quarterly/annual M&E reports are submitted on schedule with validated figures.'],
            ['Verification', 1.5, true, 'Spot checks / data verification visits are conducted and findings shared with implementing departments.'],
            ['Learning', 1.25, true, 'M&E findings feed planning and budgeting discussions with documented management responses.'],
            ['Partner reporting', 1.25, true, 'Donor/partner reporting templates and deadlines are met without material data errors.'],
            ['Capacity', 1.0, true, 'Departmental M&E focal persons are oriented and supported to submit quality data.'],
            ['Dashboarding', 1.0, true, 'Key performance dashboards are updated and accessible to authorised decision-makers.'],
            ['Evaluation', 1.25, true, 'Scheduled evaluations/reviews have TOR, ethics clearance where needed, and management uptake plans.'],
        ];
    }

    /** @return list<array{0: string, 1: float|int, 2: bool, 3: string}> */
    private function healthSciencesCriteria(): array
    {
        return [
            ['Programme standards', 1.75, true, 'Nursing/health programmes meet current professional council/TVETA curriculum and clinical hour requirements.'],
            ['Clinical placement', 1.75, true, 'Placement sites are approved; MoUs are current; student-to-supervisor ratios meet regulatory expectations.'],
            ['Skills laboratory', 1.5, true, 'Skills lab inventory, OSCE stations, and simulation scenarios cover required competencies for the cohort.'],
            ['Competence assessment', 1.5, true, 'Clinical assessment tools are validated, completed, and moderated; incomplete logs are escalated.'],
            ['Patient safety', 1.5, true, 'Infection prevention, consent, and patient safety teaching are embedded and assessed in practice.'],
            ['Licensure readiness', 1.25, true, 'Candidates for professional exams are supported with structured revision and documentation checks.'],
            ['Faculty credentials', 1.5, true, 'Teaching staff hold current professional licences/registrations required for the subjects they teach.'],
            ['Student fitness', 1.25, true, 'Medical clearance, immunisation, and fitness-to-practice checks are completed before clinical exposure.'],
            ['Incident management', 1.25, true, 'Clinical incidents/near misses involving students are reported, investigated, and used for learning.'],
            ['Community practice', 1.0, true, 'Community health postings have learning objectives, supervision, and evaluation records.'],
            ['Programme review', 1.25, true, 'Annual programme self-assessment against council standards is completed with an improvement plan.'],
        ];
    }

    /** @return list<array{0: string, 1: float|int, 2: bool, 3: string}> */
    private function businessCriteria(): array
    {
        return [
            ['Curriculum currency', 1.5, true, 'Business/accounting units reflect current standards (tax, reporting frameworks, digital tools) used in industry.'],
            ['Practical application', 1.5, true, 'Learners use relevant software (accounting packages, spreadsheets) with assessed practical tasks.'],
            ['Assessment authenticity', 1.5, true, 'Assignments and exams minimise opportunity for malpractice; originality checks are applied where required.'],
            ['Industry linkage', 1.25, true, 'Guest speakers, firm visits, or attachments are planned and evaluated each academic year.'],
            ['Ethics in business', 1.25, true, 'Professional ethics and anti-fraud awareness are taught and assessed in relevant units.'],
            ['Staff expertise', 1.25, true, 'Tutors maintain relevant professional CPD (e.g. accounting body membership) where applicable.'],
            ['Learner support', 1.0, true, 'Remedial support is available for quantitative units with documented sessions.'],
            ['Resource adequacy', 1.0, true, 'Case materials, textbooks, and licensed software seats are adequate for enrolled numbers.'],
            ['Programme review', 1.25, true, 'Employer advisory input informs periodic curriculum refresh for business programmes.'],
        ];
    }

    /** @return list<array{0: string, 1: float|int, 2: bool, 3: string}> */
    private function hospitalityCriteria(): array
    {
        return [
            ['Kitchen & lab safety', 1.75, true, 'Food production areas meet hygiene and safety standards; PPE and SOPs are enforced during practicals.'],
            ['Practical competence', 1.5, true, 'Competency checklists for culinary/service skills are completed and signed by qualified assessors.'],
            ['Equipment & consumables', 1.5, true, 'Kitchen equipment is serviceable; food consumables are controlled for spoilage, cost, and learning adequacy.'],
            ['Customer service training', 1.25, true, 'Front-of-house / service practicals include assessed customer service and communication standards.'],
            ['Industry attachment', 1.5, true, 'Hotel/restaurant attachments are supervised with logbooks and host evaluations on file.'],
            ['Menu & cost control teaching', 1.0, true, 'Learners practise costing, portion control, and waste management with assessed exercises.'],
            ['Event catering', 1.0, true, 'Institutional events used as learning sites have planning documents, risk checks, and post-mortems.'],
            ['Health certification', 1.25, true, 'Food handler medical certificates for learners/staff in practical areas are current where required.'],
            ['Programme review', 1.0, true, 'Industry feedback informs updates to hospitality practical modules.'],
        ];
    }

    /** @return list<array{0: string, 1: float|int, 2: bool, 3: string}> */
    private function technicalCriteria(): array
    {
        return [
            ['Workshop safety', 1.75, true, 'Workshops enforce PPE, machine guarding, and lock-out procedures; incident logs are maintained.'],
            ['Practical curriculum', 1.5, true, 'Technical practicals match approved syllabi with adequate contact hours and materials.'],
            ['Equipment readiness', 1.5, true, 'Tools and machines are inventoried, calibrated/serviced, and tagged when unsafe.'],
            ['Competence assessment', 1.5, true, 'Skills assessments use standard job sheets / marking guides and are moderated.'],
            ['Industry standards', 1.25, true, 'Teaching references current trade standards and codes of practice relevant to the trades offered.'],
            ['Attachment', 1.25, true, 'Industrial attachment placements are quality-assured with host evaluations.'],
            ['Environmental care', 1.0, true, 'Workshop waste, oils, and hazardous materials are disposed of according to environmental SOPs.'],
            ['Staff competence', 1.25, true, 'Technical instructors hold required trade certifications and refresher training.'],
            ['Learner safety briefing', 1.0, true, 'Safety induction is completed and signed before learners operate machinery.'],
        ];
    }

    /** @return list<array{0: string, 1: float|int, 2: bool, 3: string}> */
    private function genericAcademicCriteria(string $deptName): array
    {
        return [
            ['Programme delivery', 1.5, true, "{$deptName}: approved schemes of work and lesson plans are available for all active units."],
            ['Assessment quality', 1.5, true, "{$deptName}: assessments are moderated and results submitted within institutional deadlines."],
            ['Learner support', 1.25, true, "{$deptName}: at-risk learners are identified and supported with documented interventions."],
            ['Resources', 1.0, true, "{$deptName}: teaching resources and facilities for the department’s units are adequate and safe."],
            ['Continuous improvement', 1.25, true, "{$deptName}: course evaluation feedback leads to documented improvement actions."],
            ['Staffing', 1.25, true, "{$deptName}: staffing loads are monitored and gaps escalated to management."],
            ['Records', 1.25, true, "{$deptName}: marks, attendance, and progression records are complete and auditable."],
            ['Compliance', 1.0, true, "{$deptName}: department complies with academic and QA reporting requirements."],
        ];
    }

    /** @return list<array{0: string, 1: float|int, 2: bool, 3: string}> */
    private function genericAdminCriteria(string $deptName): array
    {
        return [
            ['Service delivery', 1.5, true, "{$deptName}: service charter / turnaround standards are defined and monitored."],
            ['Process control', 1.5, true, "{$deptName}: core SOPs are documented, current, and followed by staff."],
            ['Records', 1.25, true, "{$deptName}: transactional and correspondence records are complete and retrievable."],
            ['Risk & compliance', 1.25, true, "{$deptName}: compliance obligations and operational risks are reviewed at least quarterly."],
            ['Customer feedback', 1.0, true, "{$deptName}: complaints/feedback are logged and closed with root-cause notes where needed."],
            ['Capacity', 1.0, true, "{$deptName}: staff training needs linked to service gaps are identified and addressed."],
            ['Resource stewardship', 1.0, true, "{$deptName}: budgets and assets under the department are controlled and reported."],
            ['Continuous improvement', 1.0, true, "{$deptName}: improvement actions from audits or management reviews are tracked to closure."],
        ];
    }

    /**
     * @param  list<array{0: string, 1: float|int, 2: bool, 3: string}>  $rows
     * @return list<array{text: string, category: string, weight: float|int, max_score: int, requires_evidence: bool}>
     */
    private function mapCriteria(array $rows): array
    {
        return array_map(
            static fn (array $row) => [
                'category' => $row[0],
                'weight' => $row[1],
                'requires_evidence' => $row[2],
                'max_score' => 100,
                'text' => $row[3],
            ],
            $rows,
        );
    }
}
