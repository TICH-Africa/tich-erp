<?php

return [
    'employment_categories' => [
        'permanent' => 'Permanent',
        'contract' => 'Contract',
        'intern' => 'Intern',
        'visiting' => 'Visiting',
        'casual' => 'Casual',
        'probation' => 'Probation',
        'consultancy' => 'Consultancy',
        'consultant' => 'Consultant',
        'independent_contractor' => 'Independent contractor',
        'volunteer' => 'Volunteer (unpaid)',
    ],

    'contract_types' => [
        'permanent' => 'Permanent',
        'contract' => 'Contract',
        'intern' => 'Intern',
        'visiting' => 'Visiting',
        'casual' => 'Casual',
        'probation' => 'Probation',
        'consultancy' => 'Consultancy',
        'volunteer' => 'Volunteer (unpaid)',
    ],

    'unpaid_employment_categories' => [
        'volunteer',
    ],

    /*
     | Paid placements with no PAYE, NSSF, SHA/SHIF, or Affordable Housing Levy (e.g. interns).
     */
    'stipend_employment_categories' => [
        'intern',
    ],

    'payroll_schemes' => [
        'employee' => 'Employee (PAYE + statutory)',
        'withholding' => 'Withholding tax only',
        'unpaid' => 'Volunteer (unpaid)',
        'stipend' => 'Intern / stipend (no statutory deductions)',
    ],

    /*
     | Permanent and fixed-term contract employees: PAYE, NSSF, SHA/SHIF, and Affordable Housing Levy.
     */
    'employee_statutory_employment_categories' => [
        'permanent',
        'contract',
    ],

    'withholding_employment_categories' => [
        'consultant',
        'independent_contractor',
        'consultancy',
    ],

    'default_withholding_rate' => 5,

    /*
     | Employee statutory amounts on monthly gross / pensionable pay (Kenya, Feb 2026 schedule).
     | PAYE bands remain editable under HR → Payroll → Settings; NSSF tiers, SHIF floor, and AHL
     | use these rules in KenyaStatutoryPayrollCalculator.
     */
    'kenya_statutory' => [
        'nssf' => [
            'rate_percent' => 6,
            'tier1_ceiling' => 9000,
            'tier1_max_employee' => 540,
            'tier2_ceiling' => 108000,
            'tier2_max_employee' => 5940,
            'max_employee' => 6480,
        ],
        'shif' => [
            'rate_percent' => 2.75,
            'monthly_floor' => 300,
            /** Gross at or below this uses the flat floor only. */
            'floor_applies_up_to_gross' => 10909,
        ],
        'ahl' => [
            'rate_percent' => 1.5,
        ],
    ],

    /*
     | Kenya employee payroll (KRA) — structure provisioned on HR → Payroll → Settings.
     */
    'kenya_defaults' => [
        'personal_relief' => 2400,
        'paye_bands' => [
            ['label' => 'First KES 24,000', 'min_amount' => 0, 'max_amount' => 24000, 'rate_percent' => 10, 'display_order' => 1],
            ['label' => 'KES 24,001 – 32,333', 'min_amount' => 24000, 'max_amount' => 32333, 'rate_percent' => 25, 'display_order' => 2],
            ['label' => 'KES 32,334 – 500,000', 'min_amount' => 32333, 'max_amount' => 500000, 'rate_percent' => 30, 'display_order' => 3],
            ['label' => 'KES 500,001 – 800,000', 'min_amount' => 500000, 'max_amount' => 800000, 'rate_percent' => 32.5, 'display_order' => 4],
            ['label' => 'Above KES 800,000', 'min_amount' => 800000, 'max_amount' => null, 'rate_percent' => 35, 'display_order' => 5],
        ],
        'statutory_deductions' => [
            [
                'code' => 'nssf',
                'label' => 'NSSF',
                'value_type' => 'global_fixed',
                'reduces_taxable' => true,
                'display_order' => 2,
            ],
            [
                'code' => 'sha',
                'label' => 'SHA / SHIF',
                'value_type' => 'global_fixed',
                'reduces_taxable' => true,
                'display_order' => 3,
            ],
            [
                'code' => 'ahl',
                'label' => 'Affordable Housing Levy (AHL)',
                'value_type' => 'global_fixed',
                'reduces_taxable' => true,
                'display_order' => 4,
            ],
        ],
        'locked_deduction_codes' => [
            'personal_relief',
            'nssf',
            'sha',
            'ahl',
            'withholding_tax',
        ],
    ],
];
