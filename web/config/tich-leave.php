<?php

/**
 * Hard-coded institutional leave catalog (source of truth).
 * leave_types rows are synced from this file for FK identity only.
 */
return [

    'annual_monthly_accrual' => 1.75,
    'annual_days_per_year' => 21,
    'annual_carry_forward_max' => 10,

    'family_relations' => ['mother', 'father', 'child', 'spouse'],

    /*
    |--------------------------------------------------------------------------
    | Leave types
    |--------------------------------------------------------------------------
    | calculation: working_days = Mon–Fri skipping public holidays
    |              calendar_days = every day including weekends + public holidays
    | available: false hides type from applications (e.g. compensatory on hold)
    */
    'types' => [
        'ANNUAL' => [
            'leave_name' => 'Annual Leave',
            'days_allowed_per_year' => 21,
            'accrual' => 'monthly',
            'accrual_rate' => 1.75,
            'calculation' => 'working_days',
            'carry_forward_days' => 10,
            'requires_document' => false,
            'document_label' => null,
            'gender_restriction' => 'any',
            'available' => true,
            'description' => 'Accrues 1.75 working days on the 1st of each month from the join month. Max 21 per calendar year. Up to 10 unused days may be carried forward (line manager + HR).',
        ],
        'SICK' => [
            'leave_name' => 'Sick Leave',
            'days_allowed_per_year' => 14,
            'accrual' => 'none',
            'accrual_rate' => null,
            'calculation' => 'working_days',
            'carry_forward_days' => 0,
            'requires_document' => true,
            'document_label' => 'Medical certificate or doctor appointment proof',
            'gender_restriction' => 'any',
            'available' => true,
            'full_pay_days' => 7,
            'half_pay_days' => 7,
            'description' => '14 working days per calendar year: first 7 at full pay, next 7 at half pay. Medical document mandatory.',
        ],
        'COMP' => [
            'leave_name' => 'Compassionate Leave',
            'days_allowed_per_year' => 5,
            'accrual' => 'none',
            'accrual_rate' => null,
            'calculation' => 'working_days',
            'carry_forward_days' => 0,
            'requires_document' => false,
            'document_label' => null,
            'gender_restriction' => 'any',
            'available' => true,
            'requires_family_relation' => true,
            'family_event' => 'illness',
            'description' => '5 working days when mother, father, child, or spouse is sick.',
        ],
        'BEREAVEMENT' => [
            'leave_name' => 'Bereavement Leave',
            'days_allowed_per_year' => 5,
            'accrual' => 'none',
            'accrual_rate' => null,
            'calculation' => 'working_days',
            'carry_forward_days' => 0,
            'requires_document' => false,
            'document_label' => null,
            'gender_restriction' => 'any',
            'available' => true,
            'requires_family_relation' => true,
            'family_event' => 'death',
            'description' => '5 working days when mother, father, child, or spouse passes away.',
        ],
        'MAT' => [
            'leave_name' => 'Maternity Leave',
            'days_allowed_per_year' => 90,
            'accrual' => 'none',
            'accrual_rate' => null,
            'calculation' => 'calendar_days',
            'carry_forward_days' => 0,
            'requires_document' => true,
            'document_label' => 'Letter from the doctor',
            'gender_restriction' => 'female_only',
            'available' => true,
            'description' => '90 calendar days (includes weekends and public holidays).',
        ],
        'PAT' => [
            'leave_name' => 'Paternity Leave',
            'days_allowed_per_year' => 14,
            'accrual' => 'none',
            'accrual_rate' => null,
            'calculation' => 'calendar_days',
            'carry_forward_days' => 0,
            'requires_document' => true,
            'document_label' => 'Birth notification',
            'gender_restriction' => 'male_only',
            'available' => true,
            'description' => '14 calendar days (includes weekends and public holidays).',
        ],
        'ADOPT' => [
            'leave_name' => 'Adoption Leave',
            'days_allowed_per_year' => 30,
            'accrual' => 'none',
            'accrual_rate' => null,
            'calculation' => 'calendar_days',
            'carry_forward_days' => 0,
            'requires_document' => true,
            'document_label' => 'Adoption papers (court order, children’s department letter, or equivalent)',
            'gender_restriction' => 'any',
            'available' => true,
            'description' => '30 calendar days (includes weekends and public holidays). At least one supporting document required.',
        ],
        'COMPOFF' => [
            'leave_name' => 'Compensatory Leave',
            'days_allowed_per_year' => 0,
            'accrual' => 'none',
            'accrual_rate' => null,
            'calculation' => 'working_days',
            'carry_forward_days' => 0,
            'requires_document' => false,
            'document_label' => null,
            'gender_restriction' => 'any',
            'available' => false,
            'description' => 'Unavailable — on hold.',
        ],
    ],
];
