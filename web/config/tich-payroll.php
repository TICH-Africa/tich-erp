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
];
