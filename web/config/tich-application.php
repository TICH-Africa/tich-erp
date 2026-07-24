<?php

return [

    'session_key' => 'tich_application_draft',

    'steps' => [
        1 => ['key' => 'program', 'label' => 'Programme'],
        2 => ['key' => 'personal', 'label' => 'Personal details'],
        3 => ['key' => 'academic', 'label' => 'Qualifications'],
        4 => ['key' => 'sponsorship', 'label' => 'Sponsorship'],
        5 => ['key' => 'documents', 'label' => 'Documents'],
        6 => ['key' => 'next_of_kin', 'label' => 'Next of kin'],
        7 => ['key' => 'review', 'label' => 'Review & submit'],
    ],

    'entry_qualifications' => [
        'kcse' => 'KCSE certificate',
        'class8' => 'Class 8 / Primary certificate',
        'certificate' => 'Certificate (post-secondary)',
        'diploma' => 'Diploma',
        'rpl' => 'Recognition of Prior Learning (RPL)',
    ],

    'sponsorship_options' => [
        'self' => 'Self-sponsored',
        'parent' => 'Parent / Guardian',
        'organization' => 'Organization / Sponsor',
    ],

    'next_of_kin_relationships' => [
        'parent' => 'Parent',
        'guardian' => 'Guardian',
        'sibling' => 'Sibling',
        'spouse' => 'Spouse',
        'relative' => 'Relative',
        'friend' => 'Friend',
    ],

    'document_types' => [
        'id_copy' => 'National ID or passport copy',
        'kcse_slip' => 'KCSE result slip / certificate',
        'kcse_school_leaving' => 'KCSE school leaving certificate',
        'passport_photo' => 'Passport-size photo',
    ],

    /*
    | Per-document upload constraints shown in the form and enforced on upload.
    | max_kb is applied to Laravel's file max rule (kilobytes).
    */
    'document_upload_rules' => [
        'passport_photo' => [
            'accept' => 'image/jpeg,image/png,image/webp,.jpg,.jpeg,.png,.webp',
            'mimes' => 'jpg,jpeg,png,webp',
            'max_kb' => 2048,
            'hint' => 'Upload a passport-size photo — JPEG, PNG, or WebP only (max 2 MB). PDFs are not accepted.',
        ],
        'default' => [
            'accept' => '.pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png',
            'mimes' => 'pdf,jpg,jpeg,png',
            'max_kb' => 5120,
            'hint' => 'PDF or image file (max 5 MB).',
        ],
    ],

    'admission_fee_notice' => 'Congratulations on being shortlisted. To proceed with admission processing, you are required to pay an admission fee. The Finance Department will create your fee invoice and send payment instructions to this email address. Admission cannot be finalized until the fee is confirmed.',

    'fallback_review_emails' => [
        'admissions@tich.ac.ke',
        'admin@tich.ac.ke',
        'osumbaevans21@gmail.com',
    ],

    'counties' => [
        'Baringo', 'Bomet', 'Bungoma', 'Busia', 'Elgeyo-Marakwet', 'Embu', 'Garissa', 'Homa Bay',
        'Isiolo', 'Kajiado', 'Kakamega', 'Kericho', 'Kiambu', 'Kilifi', 'Kirinyaga', 'Kisii',
        'Kisumu', 'Kitui', 'Kwale', 'Laikipia', 'Lamu', 'Machakos', 'Makueni', 'Mandera',
        'Marsabit', 'Meru', 'Migori', 'Mombasa', 'Murang\'a', 'Nairobi', 'Nakuru', 'Nandi',
        'Narok', 'Nyamira', 'Nyandarua', 'Nyeri', 'Samburu', 'Siaya', 'Taita-Taveta', 'Tana River',
        'Tharaka-Nithi', 'Trans Nzoia', 'Turkana', 'Uasin Gishu', 'Vihiga', 'Wajir', 'West Pokot',
    ],

];
