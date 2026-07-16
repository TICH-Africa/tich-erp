<?php

return [

    'carousel' => [
        [
            'title' => 'Train for community health impact',
            'subtitle' => 'Certificate and diploma programmes in community health practice, development, and health technology — accredited by NITA and CDACC.',
            'image_path' => null,
            'cta_label' => 'Apply now',
            'cta_url' => '/apply',
        ],
        [
            'title' => 'Research that reaches communities',
            'subtitle' => 'Field-based research across Western Kenya, linking classrooms to county health systems and local partners.',
            'image_path' => null,
            'cta_label' => 'Explore research',
            'cta_url' => '#research',
        ],
        [
            'title' => 'Multi-campus, one mission',
            'subtitle' => 'From the main campus to community colleges and sub-county hubs — education rooted in community health and development.',
            'image_path' => null,
            'cta_label' => 'View programmes',
            'cta_url' => '#programs',
        ],
    ],

    'programs' => [
        [
            'program_code' => 'CHP',
            'program_name' => 'Certificate in Community Health Practice',
            'program_type' => 'certificate',
            'regulatory_body' => 'NITA',
            'duration_months' => 12,
            'homepage_tagline' => 'Frontline community health skills for CHPs and health promoters.',
            'entry_requirements' => 'KCSE mean grade D+ or equivalent; passion for community service.',
            'fee_display' => 'Contact admissions for current fee structure',
            'apply_url' => '/apply',
        ],
        [
            'program_code' => 'CHD',
            'program_name' => 'Diploma in Community Health Development',
            'program_type' => 'diploma',
            'regulatory_body' => 'CDACC',
            'duration_months' => 24,
            'homepage_tagline' => 'Lead community health programmes and development initiatives.',
            'entry_requirements' => 'KCSE mean grade C- or CHP certificate with experience.',
            'fee_display' => 'Contact admissions for current fee structure',
            'apply_url' => '/apply',
        ],
        [
            'program_code' => 'HDT',
            'program_name' => 'Health & Development Technician',
            'program_type' => 'diploma',
            'regulatory_body' => 'TVET',
            'duration_months' => 18,
            'homepage_tagline' => 'Technical skills for health systems support and development work.',
            'entry_requirements' => 'KCSE mean grade C- with passes in Maths and English.',
            'fee_display' => 'Contact admissions for current fee structure',
            'apply_url' => '/apply',
        ],
    ],

    'research' => [
        'title' => 'Community-led health systems research',
        'summary' => 'Our research hub connects students, faculty, and county partners to study maternal health, nutrition, WASH, and primary care access across Western Kenya.',
        'status' => 'ongoing',
        'url' => '#research',
    ],

    'events' => [
        [
            'title' => 'TICH Open Day 2026',
            'event_type' => 'open_day',
            'subtitle' => 'Tour campuses, meet faculty, and learn about admissions.',
            'start_datetime' => '2026-08-15 09:00:00',
            'venue' => 'Main Campus, Kisumu',
            'registration_url_or_form' => '/apply',
        ],
        [
            'title' => 'Community Health Symposium',
            'event_type' => 'conference',
            'subtitle' => 'Annual gathering of practitioners, students, and county health teams.',
            'start_datetime' => '2026-09-20 08:00:00',
            'venue' => 'TICH Conference Centre',
            'registration_url_or_form' => null,
        ],
        [
            'title' => 'Outreach Health Drive',
            'event_type' => 'outreach',
            'subtitle' => 'Student-led screening and health education in partner sub-counties.',
            'start_datetime' => '2026-10-05 07:30:00',
            'venue' => 'Homa Bay County',
            'registration_url_or_form' => null,
        ],
    ],

    'blog_posts' => [
        [
            'title' => 'Why community health education matters now',
            'slug' => 'why-community-health-education-matters',
            'excerpt' => 'How TICH prepares practitioners to serve at the frontline of primary health care across Kenya.',
            'published_at' => '2026-06-01 10:00:00',
            'reading_time_minutes' => 4,
            'featured_image_path' => null,
        ],
        [
            'title' => 'Student outreach in Homa Bay County',
            'slug' => 'student-outreach-homa-bay',
            'excerpt' => 'Nursing and CHP students supported maternal health screening during a recent field placement.',
            'published_at' => '2026-05-18 14:00:00',
            'reading_time_minutes' => 3,
            'featured_image_path' => null,
        ],
        [
            'title' => 'Admissions guide for 2026 intake',
            'slug' => 'admissions-guide-2026',
            'excerpt' => 'Programme options, entry requirements, and how to submit your application online.',
            'published_at' => '2026-05-01 09:00:00',
            'reading_time_minutes' => 5,
            'featured_image_path' => null,
        ],
    ],

];
