<?php

/**
 * Institutional tiered programmes (Certificate → Diploma → Advanced Diploma).
 * Not used for regulatory-body programmes (NITA, CDACC, etc.).
 */
return [

    'tier_keys' => [
        'certificate',
        'diploma',
        'advanced_diploma',
    ],

    'tier_labels' => [
        'certificate' => 'Certificate',
        'diploma' => 'Diploma',
        'advanced_diploma' => 'Advanced Diploma',
    ],

    /** Legacy programme type slug replaced in catalogues. */
    'legacy_degree_type' => 'degree',

    'replacement_degree_type' => 'advanced_diploma',

];
