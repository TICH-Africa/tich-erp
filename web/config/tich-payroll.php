<?php

return [
    'employment_categories' => [
        'permanent' => 'Permanent',
        'contract' => 'Contract',
        'intern' => 'Intern',
        'visiting' => 'Visiting',
        'casual' => 'Casual',
        'consultant' => 'Consultant',
        'independent_contractor' => 'Independent contractor',
    ],

    'payroll_schemes' => [
        'employee' => 'Employee (PAYE + statutory)',
        'withholding' => 'Withholding tax only',
    ],

    'withholding_employment_categories' => [
        'consultant',
        'independent_contractor',
    ],

    'default_withholding_rate' => 5,
];
