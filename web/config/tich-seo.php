<?php

/**
 * Default public SEO copy (overridable per page via @section / $seo).
 */
return [

    'defaults' => [
        'robots' => 'index,follow',
        'og_type' => 'website',
        'twitter_card' => 'summary_large_image',
    ],

    'pages' => [
        'home' => [
            'title' => 'Home',
            'description' => 'Tropical Institute of Community Health and Development in Africa (TICH) - community health education, academic programmes, research, and careers across Africa.',
        ],
        'about' => [
            'title' => 'About Us',
            'description' => 'Learn about TICH in Africa - our mission, history, and commitment to community health education and sustainable development.',
        ],
        'research' => [
            'title' => 'Research',
            'description' => 'Explore TICH research initiatives, publications, and partnerships advancing community health and development in Africa.',
        ],
        'support' => [
            'title' => 'Support Us',
            'description' => 'Support TICH Fund - donate to academic and vocational training, community health leadership, and sustainable development programmes.',
        ],
        'contact' => [
            'title' => 'Contact Us',
            'description' => 'Contact TICH in Africa for admissions, programmes, partnerships, and general enquiries. Kisumu campus and departmental contacts.',
        ],
        'events' => [
            'title' => 'Events',
            'description' => 'Upcoming and past events at TICH in Africa - workshops, conferences, graduations, and community engagement.',
        ],
        'blog' => [
            'title' => 'Blog',
            'description' => 'News, insights, and stories from TICH in Africa on community health education, research, and institutional life.',
        ],
        'programs' => [
            'title' => 'Programs & Courses',
            'description' => 'Browse TICH academic programmes and courses in community health, development, and related fields.',
        ],
        'careers' => [
            'title' => 'Careers',
            'description' => 'Join TICH in Africa - view open vacancies and career opportunities in community health education and institutional services.',
        ],
        'apply' => [
            'title' => 'Apply',
            'description' => 'Apply to study at TICH in Africa. Start or continue your admissions application online.',
        ],
    ],

];
