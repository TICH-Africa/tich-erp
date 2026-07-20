<?php

return [

    'header' => [
        ['label' => 'Home', 'url' => '/'],
        ['label' => 'About Us', 'url' => '#about'],
        ['label' => 'Research', 'url' => '#research'],
        ['label' => 'Academics', 'url' => '#programs'],
        ['label' => 'Programs/Courses', 'url' => '/programs'],
        ['label' => 'Events', 'url' => '#events'],
        ['label' => 'Blog', 'url' => '#blog'],
    ],

    'footer_primary' => [
        ['label' => 'Home', 'url' => '/'],
        ['label' => 'About Us', 'url' => '#about'],
        ['label' => 'Research Portal', 'url' => '#research'],
        ['label' => 'Academics Catalog', 'url' => '#programs'],
        ['label' => 'Programs & Courses', 'url' => '/programs'],
    ],

    'footer_quick_links' => [
        ['label' => 'Student Portal', 'url' => 'route:login', 'requires_auth' => false],
        ['label' => 'Tutor Portal', 'url' => 'route:login', 'requires_auth' => false],
        ['label' => 'Staff ESS', 'url' => 'route:login', 'requires_auth' => false],
        ['label' => 'SACCO Login', 'url' => 'route:login', 'requires_auth' => false],
        ['label' => 'Careers', 'url' => '#careers'],
    ],

    'contact' => [
        ['channel_type' => 'physical_address', 'label' => 'Main Campus', 'display_value' => 'Kisumu, Kenya'],
        ['channel_type' => 'email', 'label' => 'Admissions', 'display_value' => 'admissions@tich.ac.ke', 'value' => 'admissions@tich.ac.ke'],
        ['channel_type' => 'phone', 'label' => 'General enquiries', 'display_value' => '+254 700 000 000', 'value' => '+254700000000'],
    ],

    'social' => [
        ['platform' => 'twitter_x', 'display_name' => 'X (Twitter)', 'url' => 'https://twitter.com/tichinafrica', 'icon_name' => 'x'],
        ['platform' => 'linkedin', 'display_name' => 'LinkedIn', 'url' => 'https://linkedin.com/company/tich', 'icon_name' => 'linkedin'],
        ['platform' => 'facebook', 'display_name' => 'Facebook', 'url' => 'https://facebook.com/tichinafrica', 'icon_name' => 'facebook'],
        ['platform' => 'youtube', 'display_name' => 'YouTube', 'url' => 'https://youtube.com/@tichinafrica', 'icon_name' => 'youtube'],
    ],

    'site' => [
        'institution_name' => 'Tropical Institute of Community Health and Development in Africa',
        'short_name' => 'TICH in Africa',
        'tagline' => 'Community health education for Africa',
        'copyright' => 'Tropical Institute of Community Health and Development in Africa',
    ],

];
