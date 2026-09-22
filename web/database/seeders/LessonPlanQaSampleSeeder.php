<?php

namespace Database\Seeders;

use App\Models\LessonPlan;
use App\Models\Staff;
use App\Models\UnitAllocation;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Sample lesson plans for QA acknowledgement testing.
 * Anchored to evans777999@gmail.com (HOD / Lecturer in Health and Social Sciences).
 */
class LessonPlanQaSampleSeeder extends Seeder
{
    private const EMAIL = 'evans777999@gmail.com';

    public function run(): void
    {
        $evans = User::query()->where('email', self::EMAIL)->first();
        if (! $evans || ! $evans->staff_id) {
            $this->command?->warn('User '.self::EMAIL.' not found with a staff profile; skipped.');

            return;
        }

        $hodStaff = Staff::query()->find($evans->staff_id);
        if (! $hodStaff) {
            $this->command?->warn('Staff profile for '.self::EMAIL.' missing; skipped.');

            return;
        }

        $departmentId = (int) (DB::table('departments')->where('hod_id', $hodStaff->id)->value('id')
            ?: $hodStaff->department_id);
        if ($departmentId < 1) {
            $this->command?->warn('No department found for '.self::EMAIL.'; skipped.');

            return;
        }

        $allocations = UnitAllocation::query()
            ->whereHas('unit', fn ($q) => $q->where('department_id', $departmentId))
            ->orderBy('id')
            ->limit(12)
            ->get();

        if ($allocations->isEmpty()) {
            $this->command?->warn('No unit allocations in department '.$departmentId.'; skipped.');

            return;
        }

        $tutorStaffId = (int) ($allocations->first(fn ($a) => (int) $a->staff_id !== (int) $hodStaff->id)?->staff_id
            ?: $hodStaff->id);
        $tutorStaff = Staff::query()->find($tutorStaffId) ?: $hodStaff;

        $allocations = $allocations
            ->filter(fn ($a) => in_array((int) $a->staff_id, [(int) $tutorStaff->id, (int) $hodStaff->id], true))
            ->values();

        if ($allocations->isEmpty()) {
            $this->command?->warn('No matching tutor allocations for sample lesson plans; skipped.');

            return;
        }

        $samples = [
            [
                'plan_number' => 'LP-SAMPLE-QA-SUBMITTED',
                'status' => 'submitted',
                'prepared_by' => $tutorStaff->id,
                'allocation' => $allocations[0] ?? null,
                'hod_id' => null,
                'hod_action_at' => null,
                'hod_comments' => null,
                'qa_acknowledged_by' => null,
                'qa_acknowledged_at' => null,
                'qa_comments' => null,
                'title' => 'Community health needs assessment briefing',
                'week' => 3,
                'theme' => 'needs_assessment',
            ],
            [
                'plan_number' => 'LP-SAMPLE-QA-PENDING-ACK',
                'status' => 'approved',
                'prepared_by' => $tutorStaff->id,
                'allocation' => $allocations[1] ?? $allocations[0],
                'hod_id' => $hodStaff->id,
                'hod_action_at' => now()->subDays(2),
                'hod_comments' => 'Approved for delivery. Ensure participatory methods are documented.',
                'qa_acknowledged_by' => null,
                'qa_acknowledged_at' => null,
                'qa_comments' => null,
                'title' => 'Maternal health promotion session plan',
                'week' => 4,
                'theme' => 'maternal',
            ],
            [
                'plan_number' => 'LP-SAMPLE-QA-ACKED',
                'status' => 'approved',
                'prepared_by' => $tutorStaff->id,
                'allocation' => $allocations[2] ?? $allocations[0],
                'hod_id' => $hodStaff->id,
                'hod_action_at' => now()->subDays(5),
                'hod_comments' => 'Approved.',
                'qa_acknowledged_by' => $this->resolveQaStaffId(),
                'qa_acknowledged_at' => now()->subDay(),
                'qa_comments' => 'Acknowledged. Learning outcomes align with programme standards.',
                'title' => 'Nutrition counselling practical',
                'week' => 5,
                'theme' => 'nutrition',
            ],
            [
                'plan_number' => 'LP-SAMPLE-QA-HOD-TUTOR',
                'status' => 'submitted',
                'prepared_by' => $hodStaff->id,
                'allocation' => $allocations->first(fn ($a) => (int) $a->staff_id === (int) $hodStaff->id) ?: $allocations[0],
                'hod_id' => null,
                'hod_action_at' => null,
                'hod_comments' => null,
                'qa_acknowledged_by' => null,
                'qa_acknowledged_at' => null,
                'qa_comments' => null,
                'title' => 'Field practicum orientation (HOD as tutor)',
                'week' => 6,
                'theme' => 'practicum',
            ],
            [
                'plan_number' => 'LP-SAMPLE-QA-REJECTED',
                'status' => 'rejected',
                'prepared_by' => $tutorStaff->id,
                'allocation' => $allocations[3] ?? $allocations[0],
                'hod_id' => $hodStaff->id,
                'hod_action_at' => now()->subDays(1),
                'hod_comments' => 'Revise objectives to be measurable and add assessment criteria.',
                'qa_acknowledged_by' => null,
                'qa_acknowledged_at' => null,
                'qa_comments' => null,
                'title' => 'Environmental health walk-through draft',
                'week' => 2,
                'theme' => 'environment',
            ],
        ];

        $created = 0;
        $updated = 0;

        foreach ($samples as $sample) {
            /** @var UnitAllocation|null $allocation */
            $allocation = $sample['allocation'];
            if (! $allocation) {
                continue;
            }

            // Ensure prepared_by matches allocation tutor where possible.
            $preparedBy = (int) $sample['prepared_by'];
            if ((int) $allocation->staff_id !== $preparedBy) {
                $match = $allocations->first(fn ($a) => (int) $a->staff_id === $preparedBy);
                if ($match) {
                    $allocation = $match;
                } else {
                    $preparedBy = (int) $allocation->staff_id;
                }
            }

            $formPayload = $this->buildFormPayload($sample['title'], $sample['theme']);
            $sessionMethods = collect($formPayload['payload']['session_rows'] ?? [])
                ->pluck('trainer_activities')
                ->filter()
                ->take(3)
                ->map(fn ($value) => \Illuminate\Support\Str::limit(trim((string) $value), 80))
                ->implode('; ');

            $payload = [
                'unit_allocation_id' => $allocation->id,
                'prepared_by' => $preparedBy,
                'source_type' => 'form',
                'lesson_title' => $sample['title'],
                'lesson_objectives' => $this->specificObjectives($sample['theme'], $sample['title']),
                'topics_covered' => $sample['title'].' — introduction, core concepts, guided practice, and closure.',
                'competencies_targeted' => $this->competencies($sample['theme']),
                'contact_hours' => 2,
                'week_number' => $sample['week'],
                'planned_date' => now()->addWeeks(max(1, (int) $sample['week'] - 2))->toDateString(),
                'teaching_methods' => $sessionMethods !== ''
                    ? $sessionMethods
                    : 'Interactive lecture, group work, case discussion, demonstration.',
                'resources_required' => $formPayload['resources_line'],
                'form_payload' => array_merge($formPayload['payload'], [
                    'sample' => true,
                    'anchor_email' => self::EMAIL,
                ]),
                'tutor_verified_at' => now()->subDays(3),
                'status' => $sample['status'],
                'hod_comments' => $sample['hod_comments'],
                'hod_id' => $sample['hod_id'],
                'hod_action_at' => $sample['hod_action_at'],
                'registrar_visible' => 1,
                'qa_acknowledged_by' => $sample['qa_acknowledged_by'],
                'qa_acknowledged_at' => $sample['qa_acknowledged_at'],
                'qa_comments' => $sample['qa_comments'],
                'updated_at' => now(),
            ];

            $existing = LessonPlan::query()->where('plan_number', $sample['plan_number'])->first();
            if ($existing) {
                $existing->fill($payload)->save();
                $updated++;
            } else {
                LessonPlan::query()->create(array_merge($payload, [
                    'plan_number' => $sample['plan_number'],
                    'created_at' => now()->subDays(4),
                ]));
                $created++;
            }
        }

        $this->command?->info("Lesson plan QA samples for ".self::EMAIL.": created={$created}, updated={$updated}.");
    }

    /**
     * @return array{payload: array<string, mixed>, resources_line: string}
     */
    private function buildFormPayload(string $title, string $theme): array
    {
        $meta = $this->themeMeta($theme, $title);

        return [
            'resources_line' => $meta['resources'],
            'payload' => [
                'general_objective' => $meta['general_objective'],
                'prior_knowledge' => $meta['prior_knowledge'],
                'references' => $meta['references'],
                'assignment' => $meta['assignment'],
                'venue' => $meta['venue'],
                'session_time' => '08:00 - 10:00',
                'intake_class' => $meta['intake_class'],
                'session_rows' => $meta['session_rows'],
            ],
        ];
    }

    /**
     * @return array{
     *     general_objective: string,
     *     prior_knowledge: string,
     *     references: string,
     *     assignment: string,
     *     venue: string,
     *     intake_class: string,
     *     resources: string,
     *     session_rows: list<array{time: string, content: string, trainer_activities: string, learner_activities: string, evaluation: string}>
     * }
     */
    private function themeMeta(string $theme, string $title): array
    {
        return match ($theme) {
            'maternal' => [
                'general_objective' => 'Equip learners to plan and deliver culturally sensitive maternal health promotion sessions in community settings.',
                'prior_knowledge' => 'Basic reproductive health concepts; antenatal care pathway; communication skills from earlier CHP units.',
                'references' => "MOH Kenya Maternal and Newborn Health guidelines.\nWHO recommendations on antenatal care for a positive pregnancy experience.\nCHP programme handbook – maternal health module.",
                'assignment' => 'Prepare a one-page maternal health promotion plan for a named community group, including key messages and materials list (due next session).',
                'venue' => 'Skills lab / Classroom B2',
                'intake_class' => 'CHP Year 1 – Group A',
                'resources' => 'ANC flip charts, pregnancy wheel, IEC leaflets, markers, attendance sheet, observation checklist',
                'session_rows' => [
                    [
                        'time' => '08:00 – 08:15 (15 min)',
                        'content' => "Introduction and climate setting\n• Session topic and learning outcomes\n• Link to previous antenatal care content\n• Safety and confidentiality ground rules",
                        'trainer_activities' => "Welcome learners and take roll.\nState the general and specific objectives.\nPose an ice-breaker question on local maternal health myths.",
                        'learner_activities' => "Settle and confirm attendance.\nShare one maternal health concern known in their community.\nNote the session objectives.",
                        'evaluation' => 'Oral check: each learner states one expected takeaway for the session.',
                    ],
                    [
                        'time' => '08:15 – 08:40 (25 min)',
                        'content' => "Core concepts\n• Danger signs in pregnancy\n• Birth preparedness and complication readiness\n• Role of the CHP in referral and follow-up",
                        'trainer_activities' => "Present short lecture with slides/flip chart.\nHighlight MOH danger-sign list.\nClarify referral thresholds with examples.",
                        'learner_activities' => "Listen and annotate handouts.\nAsk clarifying questions.\nMap danger signs against local referral facilities.",
                        'evaluation' => 'Quick quiz (3 oral questions) on danger signs and when to refer.',
                    ],
                    [
                        'time' => '08:40 – 09:20 (40 min)',
                        'content' => "Guided practice – health promotion dialogue\n• Opening a maternal counselling conversation\n• Using IEC materials\n• Checking understanding and documenting",
                        'trainer_activities' => "Demonstrate a 5-minute counselling dialogue.\nDistribute role-play briefs (CHP / expectant mother / observer).\nCoach pairs and give formative feedback.",
                        'learner_activities' => "Observe the demonstration.\nPractice in triads using the briefs.\nObserver completes a simple checklist and gives peer feedback.",
                        'evaluation' => 'Peer checklist scored against greeting, key messages, teach-back, and documentation.',
                    ],
                    [
                        'time' => '09:20 – 09:45 (25 min)',
                        'content' => "Case discussion\n• Delayed ANC booking\n• Teenage pregnancy support\n• Male involvement barriers",
                        'trainer_activities' => "Facilitate small-group case analysis.\nProbe for equity, culture, and quality considerations.\nSynthesize group conclusions on the board.",
                        'learner_activities' => "Discuss assigned case in groups of 4–5.\nPropose two practical CHP actions and one facility action.\nPresent a 2-minute summary.",
                        'evaluation' => 'Group presentations assessed for relevance, feasibility, and alignment to MOH guidance.',
                    ],
                    [
                        'time' => '09:45 – 10:00 (15 min)',
                        'content' => "Closure and forward planning\n• Recap of key messages\n• Assignment briefing\n• Preview of next session",
                        'trainer_activities' => "Summarize three non-negotiable messages.\nIssue the homework brief.\nCollect feedback slips.",
                        'learner_activities' => "Restate personal action points.\nConfirm homework deadline.\nComplete exit ticket.",
                        'evaluation' => 'Exit ticket: list two danger signs and one birth-preparedness item.',
                    ],
                ],
            ],
            'nutrition' => [
                'general_objective' => 'Enable learners to conduct structured nutrition counselling using assessment, advice, and follow-up steps.',
                'prior_knowledge' => 'Basic food groups; malnutrition signs; growth monitoring concepts.',
                'references' => "MOH Infant and Young Child Feeding counselling cards.\nSphere / national nutrition guidelines.\nUnit reading pack on counselling micro-skills.",
                'assignment' => 'Complete a practice counselling form for a fictional caregiver case and attach a one-week food diversity plan.',
                'venue' => 'Nutrition counselling corner / Demonstration room',
                'intake_class' => 'CHP Year 1 – Group B',
                'resources' => 'Food models, MUAC tapes, counselling cards, sample diet charts, role-play scripts',
                'session_rows' => [
                    [
                        'time' => '08:00 – 08:10 (10 min)',
                        'content' => "Warm-up and objectives\n• Review of last week’s growth monitoring\n• Today’s counselling focus",
                        'trainer_activities' => "Recap previous lesson with 2 questions.\nDisplay today’s counselling steps poster.",
                        'learner_activities' => "Answer recap questions.\nOpen notebooks to counselling template.",
                        'evaluation' => 'Recap Q&A accuracy recorded informally.',
                    ],
                    [
                        'time' => '08:10 – 08:35 (25 min)',
                        'content' => "Counselling framework\n• Assess – Analyze – Act\n• Dietary diversity and frequency\n• Common caregiver barriers",
                        'trainer_activities' => "Explain the AAA framework.\nWalk through a completed sample form.\nHighlight do’s/don’ts of advice-giving.",
                        'learner_activities' => "Follow on handouts.\nIdentify barriers from a short vignette.\nNote phrases to avoid in counselling.",
                        'evaluation' => 'Learners label steps of AAA on a blank worksheet.',
                    ],
                    [
                        'time' => '08:35 – 09:15 (40 min)',
                        'content' => "Skills practice\n• Opening and rapport\n• Assessing dietary recall\n• Negotiating one doable change",
                        'trainer_activities' => "Live demonstration with a volunteer.\nRotate among practice pairs.\nStop-start coaching on listening skills.",
                        'learner_activities' => "Role-play counsellor and caregiver.\nComplete dietary recall section.\nAgree one SMART action with the ‘caregiver’.",
                        'evaluation' => 'Trainer spot-checks: rapport, open questions, and negotiated action quality.',
                    ],
                    [
                        'time' => '09:15 – 09:40 (25 min)',
                        'content' => "Feedback and refinement\n• Common errors\n• Documentation standards\n• Referral cues for severe malnutrition",
                        'trainer_activities' => "Debrief observed strengths/gaps.\nShow correctly completed documentation.\nClarify urgent referral criteria.",
                        'learner_activities' => "Share peer observations.\nCorrect their practice forms.\nList referral red flags.",
                        'evaluation' => 'Corrected forms reviewed against a model answer key.',
                    ],
                    [
                        'time' => '09:40 – 10:00 (20 min)',
                        'content' => "Consolidation\n• Key messages board\n• Assignment and practicum link",
                        'trainer_activities' => "Co-create a summary board with learners.\nBrief the homework case.\nLink to upcoming field practicum.",
                        'learner_activities' => "Contribute summary points.\nClarify assignment.\nPack counselling cards carefully.",
                        'evaluation' => 'Oral teach-back: one learner explains AAA to the class.',
                    ],
                ],
            ],
            'practicum' => [
                'general_objective' => 'Orient learners to field practicum expectations, supervision, documentation, and professional conduct.',
                'prior_knowledge' => 'Completed classroom modules on community engagement; signed code of conduct; basic first aid awareness.',
                'references' => "CHP field practicum guide.\nInstitutional student placement policy.\nSite MOU summary notes.",
                'assignment' => 'Submit a completed practicum readiness checklist and emergency contact card before the first site visit.',
                'venue' => 'Lecture theatre 1 + briefing foyer',
                'intake_class' => 'CHP practicum cohort – all groups',
                'resources' => 'Practicum handbook, logbooks, site maps, PPE list, attendance register, risk assessment form',
                'session_rows' => [
                    [
                        'time' => '08:00 – 08:20 (20 min)',
                        'content' => "Orientation overview\n• Practicum goals and competencies\n• Timeline and reporting lines\n• Supervision model",
                        'trainer_activities' => "Present practicum roadmap.\nIntroduce site supervisors (or profiles).\nExplain HOD and tutor escalation paths.",
                        'learner_activities' => "Follow roadmap handout.\nNote supervisor contacts.\nAsk clarifying questions.",
                        'evaluation' => 'Learners correctly identify their reporting line on a quick matching sheet.',
                    ],
                    [
                        'time' => '08:20 – 08:50 (30 min)',
                        'content' => "Professional conduct and safeguarding\n• Dress code and PPE\n• Consent and confidentiality\n• Incident reporting",
                        'trainer_activities' => "Walk through code of conduct.\nDiscuss two incident scenarios.\nShow how to complete an incident form.",
                        'learner_activities' => "Discuss scenarios in pairs.\nDraft key steps for one incident.\nSign acknowledgement of conduct rules.",
                        'evaluation' => 'Signed acknowledgement collected; scenario responses spot-checked.',
                    ],
                    [
                        'time' => '08:50 – 09:25 (35 min)',
                        'content' => "Documentation tools\n• Daily logbook\n• Activity evidence\n• Reflection prompts",
                        'trainer_activities' => "Demonstrate a filled sample log page.\nExplain quality evidence standards.\nModel a short reflective note.",
                        'learner_activities' => "Practice writing one sample log entry.\nPeer-review a neighbour’s entry.\nList evidence they will collect on day one.",
                        'evaluation' => 'Sample log entries meet completeness criteria (date, activity, learning, signature).',
                    ],
                    [
                        'time' => '09:25 – 09:45 (20 min)',
                        'content' => "Site logistics\n• Transport and reporting time\n• Site contacts\n• Risk controls",
                        'trainer_activities' => "Issue site maps and schedules.\nReview risk assessment highlights.\nConfirm buddy system rules.",
                        'learner_activities' => "Confirm personal site assignment.\nExchange buddy contacts.\nNote PPE pack list.",
                        'evaluation' => 'Roll confirmation of site/buddy pairs recorded.',
                    ],
                    [
                        'time' => '09:45 – 10:00 (15 min)',
                        'content' => "Close-out\n• Readiness checklist\n• Q&A\n• Next steps",
                        'trainer_activities' => "Run readiness checklist aloud.\nAnswer outstanding questions.\nConfirm first visit date.",
                        'learner_activities' => "Self-score readiness checklist.\nAsk final questions.\nBook any missing items with stores.",
                        'evaluation' => 'Readiness checklist ≥80% complete before dismissal.',
                    ],
                ],
            ],
            'environment' => [
                'general_objective' => 'Introduce learners to structured environmental health walk-throughs and hazard prioritisation (draft for revision).',
                'prior_knowledge' => 'Basic hygiene and sanitation concepts; awareness of common household hazards.',
                'references' => "Draft MOH environmental health inspection aide-memoire.\nWHO water, sanitation and hygiene fact sheets.",
                'assignment' => 'Revise walk-through checklist with measurable observation criteria (resubmit after HOD feedback).',
                'venue' => 'Campus grounds (pilot walk) / Classroom C1',
                'intake_class' => 'CHP Year 1 – Group C',
                'resources' => 'Draft checklist, clipboard, camera (optional), gloves, sample risk matrix',
                'session_rows' => [
                    [
                        'time' => '08:00 – 08:15 (15 min)',
                        'content' => "Session framing\n• Purpose of environmental walk-throughs\n• Difference between inspection and health education visit",
                        'trainer_activities' => "Introduce topic and draft nature of materials.\nExplain expected revisions after feedback.",
                        'learner_activities' => "Note purpose statements.\nShare one environmental hazard observed locally.",
                        'evaluation' => 'Learners distinguish inspection vs education visit in one sentence each.',
                    ],
                    [
                        'time' => '08:15 – 08:45 (30 min)',
                        'content' => "Hazard categories\n• Water and sanitation\n• Solid waste\n• Vector breeding\n• Food hygiene",
                        'trainer_activities' => "Present category cards.\nShow photo examples.\nFacilitate ranking discussion.",
                        'learner_activities' => "Sort example photos into categories.\nRank top three local risks.\nDebate prioritisation criteria.",
                        'evaluation' => 'Group ranking justified with at least one public-health reason each.',
                    ],
                    [
                        'time' => '08:45 – 09:20 (35 min)',
                        'content' => "Walk-through practice (campus pilot)\n• Observation\n• Note-taking\n• Courteous engagement",
                        'trainer_activities' => "Brief safety rules.\nLead a short outdoor walk.\nModel discreet observation notes.",
                        'learner_activities' => "Observe designated zones.\nRecord hazards on draft checklist.\nAvoid photographing people without consent.",
                        'evaluation' => 'Checklist entries include location, hazard, and suggested action.',
                    ],
                    [
                        'time' => '09:20 – 09:45 (25 min)',
                        'content' => "Debrief and gap analysis\n• Missing measurable criteria\n• Ambiguous wording\n• Assessment standards",
                        'trainer_activities' => "Facilitate critique of the draft tool.\nHighlight HOD feedback themes expected.\nCoach measurable phrasing.",
                        'learner_activities' => "Identify weak checklist items.\nRewrite two items to be measurable.\nPropose an evaluation column improvement.",
                        'evaluation' => 'Rewritten items checked for observability and clarity.',
                    ],
                    [
                        'time' => '09:45 – 10:00 (15 min)',
                        'content' => "Closure\n• Revision task\n• Resubmission path",
                        'trainer_activities' => "Summarize required revisions.\nConfirm resubmission deadline after HOD comments.",
                        'learner_activities' => "List personal revision actions.\nAsk final questions.",
                        'evaluation' => 'Exit note: two checklist improvements each learner will make.',
                    ],
                ],
            ],
            default => [
                'general_objective' => 'Enable learners to plan and facilitate a community health needs assessment briefing using participatory methods.',
                'prior_knowledge' => 'Introduction to community health; basic facilitation skills; understanding of primary health care principles.',
                'references' => "CHP needs assessment toolkit.\nParticipatory Rural Appraisal (PRA) quick guide.\nCounty community health strategy notes.",
                'assignment' => 'Draft a one-page needs assessment briefing agenda for a community dialogue day, including roles and tools.',
                'venue' => 'Community learning classroom / Hall 2',
                'intake_class' => 'CHP Year 1 – Group A',
                'resources' => 'Whiteboard, sticky notes, community map template, marker pens, attendance sheet, sample problem tree',
                'session_rows' => [
                    [
                        'time' => '08:00 – 08:15 (15 min)',
                        'content' => "Opening and orientation\n• Topic: {$title}\n• Learning outcomes\n• Link to community strategy",
                        'trainer_activities' => "Welcome and attendance.\nDisplay objectives.\nActivate prior knowledge with a rapid poll on local health priorities.",
                        'learner_activities' => "Respond to the poll.\nWrite one community priority on a sticky note.\nCluster notes on the wall.",
                        'evaluation' => 'Sticky-note clusters show at least three distinct priority themes.',
                    ],
                    [
                        'time' => '08:15 – 08:40 (25 min)',
                        'content' => "Needs assessment concepts\n• Felt vs normative needs\n• Data sources (community, facility, partners)\n• Ethical engagement",
                        'trainer_activities' => "Mini-lecture with examples.\nContrast felt and normative needs using a local vignette.\nHighlight consent and do-no-harm.",
                        'learner_activities' => "Take structured notes.\nClassify vignette statements as felt/normative.\nDiscuss ethics in pairs.",
                        'evaluation' => 'Pair work correctly classifies ≥4/5 vignette statements.',
                    ],
                    [
                        'time' => '08:40 – 09:15 (35 min)',
                        'content' => "Tool practice\n• Community mapping\n• Problem tree basics\n• Facilitator questioning",
                        'trainer_activities' => "Demonstrate mapping on the board.\nIssue group toolkits.\nCoach groups on open questions and neutrality.",
                        'learner_activities' => "In groups of 5, draft a mini community map.\nIdentify 2–3 problem roots and effects.\nAppoint a facilitator and note-taker.",
                        'evaluation' => 'Group outputs include map features, problem statement, and two root causes.',
                    ],
                    [
                        'time' => '09:15 – 09:40 (25 min)',
                        'content' => "Briefing simulation\n• Opening a community dialogue\n• Presenting findings neutrally\n• Agreeing next steps",
                        'trainer_activities' => "Set simulation roles (CHP team / community reps).\nTime-box presentations.\nProvide structured feedback.",
                        'learner_activities' => "Deliver a 4-minute briefing using group findings.\nRespond to community questions.\nCapture agreed next steps.",
                        'evaluation' => 'Rubric: clarity, neutrality, participation, and actionable next steps.',
                    ],
                    [
                        'time' => '09:40 – 10:00 (20 min)',
                        'content' => "Reflection and close\n• What worked / what to improve\n• Assignment briefing\n• Link to next week’s field task",
                        'trainer_activities' => "Facilitate plus/delta reflection.\nIssue assignment sheet.\nConfirm materials needed for field task.",
                        'learner_activities' => "Share one improvement point.\nConfirm assignment understanding.\nComplete feedback form.",
                        'evaluation' => 'Exit form: one concept learned + one facilitation skill to practice.',
                    ],
                ],
            ],
        };
    }

    private function specificObjectives(string $theme, string $title): string
    {
        return match ($theme) {
            'maternal' => "By the end of this session, learners will be able to:\n1. List at least five pregnancy danger signs requiring urgent referral.\n2. Demonstrate a structured maternal health counselling dialogue using IEC materials.\n3. Propose practical CHP actions for delayed ANC and birth preparedness gaps.",
            'nutrition' => "By the end of this session, learners will be able to:\n1. Apply the Assess–Analyze–Act counselling framework.\n2. Conduct a basic dietary recall and negotiate one feasible behavioural change.\n3. Document counselling outcomes and identify severe malnutrition referral cues.",
            'practicum' => "By the end of this session, learners will be able to:\n1. Explain practicum reporting lines, supervision, and timelines.\n2. Complete a sample daily logbook entry to the required standard.\n3. Confirm site logistics, buddy arrangements, and readiness checklist items.",
            'environment' => "By the end of this session, learners will be able to:\n1. Categorise common environmental health hazards observed during a walk-through.\n2. Record observations with location, hazard, and suggested action.\n3. Rewrite weak checklist items into measurable observation criteria.",
            default => "By the end of this session, learners will be able to:\n1. Distinguish felt and normative community health needs with examples.\n2. Facilitate a mini community mapping and problem-tree exercise.\n3. Deliver a short, neutral needs-assessment briefing with agreed next steps.\n\nFocus topic: {$title}.",
        };
    }

    private function competencies(string $theme): string
    {
        return match ($theme) {
            'maternal' => 'Maternal health promotion; interpersonal communication; referral judgement; respectful care.',
            'nutrition' => 'Nutrition counselling; assessment and documentation; behaviour change negotiation; referral for malnutrition.',
            'practicum' => 'Professional conduct; field documentation; teamwork; risk awareness and safeguarding.',
            'environment' => 'Environmental health observation; hazard prioritisation; tool critique; community courtesy.',
            default => 'Community needs assessment; participatory facilitation; ethical engagement; briefing and consensus-building.',
        };
    }

    private function resolveQaStaffId(): ?int
    {
        $userId = DB::table('user_roles as ur')
            ->join('roles as r', 'r.id', '=', 'ur.role_id')
            ->where('r.role_name', 'QA Officer')
            ->value('ur.user_id');

        if (! $userId) {
            return null;
        }

        return User::query()->where('id', $userId)->value('staff_id');
    }
}
