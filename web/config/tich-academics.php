<?php

return [

    'curriculum_formats' => ['modular', 'semester', 'trimester', 'block'],

    'curriculum_profiles' => ['standard', 'chd', 'nursing', 'vocational', 'ict'],

    'default_trimester_count' => 3,

    'default_intake_months' => ['January', 'May', 'September'],

    'unit_statuses' => ['draft', 'pending_registry', 'active', 'inactive'],

    'version_statuses' => ['draft', 'pending_registry', 'pending_ceo', 'published', 'superseded'],

    /*
    | Phase C downstream modules should read published curriculum via AcademicsIntegrationRegistry.
    */
    'integration_hooks' => [
        'workload' => 'unit_allocations',
        'timetable' => 'timetable_entries',
        'lesson_plans' => 'lesson_plans',
        'exam_eligibility' => 'exam_eligibility_matrix',
    ],

    'workload_limits' => [
        'max_contact_hours' => (int) env('TICH_WORKLOAD_MAX_HOURS', 18),
        'max_units' => (int) env('TICH_WORKLOAD_MAX_UNITS', 4),
    ],

];
