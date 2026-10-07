<?php

/**
 * Staff performance appraisal catalogue (TICH Performance Review Template + competency matrix).
 */
return [

    'rating_scale' => [
        5 => [
            'label' => 'Exceptionally high level of performance',
            'short' => 'Exceptional',
            'slug' => 'exceptional',
            'description' => 'Consistently exceeding expectations with outstanding initiative, quality, and impact.',
        ],
        4 => [
            'label' => 'High level of performance',
            'short' => 'High',
            'slug' => 'high',
            'description' => 'High-level performance recognised within the peer group and wider circle.',
        ],
        3 => [
            'label' => 'Good level of performance',
            'short' => 'Good',
            'slug' => 'good',
            'description' => 'Consistently performed well and in accordance with expectations.',
        ],
        2 => [
            'label' => 'Performance needing improvement',
            'short' => 'Needs improvement',
            'slug' => 'needs_improvement',
            'description' => 'Needs to work on improving performance.',
        ],
        1 => [
            'label' => 'Unsatisfactory performance',
            'short' => 'Unsatisfactory',
            'slug' => 'unsatisfactory',
            'description' => 'Serious performance issues requiring significant, methodical improvement.',
        ],
    ],

    /** Default weight for Objective 1 (JD duties). Remaining weight is for other goals. */
    'jd_objective_default_weight' => 40,

    /** Require goal weights to total exactly 100% before supervisor approval. */
    'require_weight_total_100' => true,

    /**
     * Overall score = (objectives_weight * objectives) + (competencies_weight * competencies).
     * Guiding tools from HR may refine this later.
     */
    'score_blend' => [
        'objectives' => 0.6,
        'competencies' => 0.4,
    ],

    'competencies' => [
        'core' => [
            'label' => 'Core competencies',
            'items' => [
                'communication_documentation' => 'Communication & Documentation',
                'collaboration_teamwork' => 'Collaboration and Teamwork',
                'judgement_ethics' => 'Judgement, ethics and Decision Making',
                'service_orientation' => 'Service Orientation & Community Focus',
                'learning_adaptability' => 'Learning, Adaptability & Growth Mindset',
                'accountability_results' => 'Accountability, reliability & Results Orientation',
            ],
        ],
        'managerial' => [
            'label' => 'Managerial competencies',
            'items' => [
                'managing_staff_performance' => 'Managing Staff Performance',
                'managing_staff_development' => 'Managing Staff Development',
                'operational_planning' => 'Operational planning and Resource Management',
                'leadership_modelling' => 'Leadership and role Modelling',
            ],
        ],
        'functional' => [
            'label' => 'Functional competencies',
            'optional' => true,
            'items' => [
                'strategic_orientation' => 'Strategic Orientation',
                'building_alliances' => 'Building Alliances and Partnerships',
                'process_improvement' => 'Process improvement and innovation',
                'compliance_policy' => 'Compliance and policy application',
            ],
        ],
    ],

    'statuses' => [
        'draft_goals' => 'Goal setting',
        'goals_pending_approval' => 'Goals awaiting supervisor',
        'self_assessment' => 'Self-assessment',
        'manager_review' => 'Manager review',
        'pending_calibration' => 'Calibration',
        'pending_hr' => 'Pending HR sign-off',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
    ],

    'cycle_statuses' => [
        'draft' => 'Draft',
        'open' => 'Open',
        'calibration' => 'Calibration',
        'closed' => 'Closed',
    ],
];
