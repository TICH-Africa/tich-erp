<?php

return [

    'session_key' => 'tich_application_draft',

    'steps' => [
        1 => ['key' => 'program', 'label' => 'Programme'],
        2 => ['key' => 'personal', 'label' => 'Personal details'],
        3 => ['key' => 'academic', 'label' => 'Qualifications'],
        4 => ['key' => 'documents', 'label' => 'Documents'],
        5 => ['key' => 'review', 'label' => 'Review & submit'],
    ],

    'entry_qualifications' => [
        'kcse' => 'KCSE certificate',
        'class8' => 'Class 8 / Primary certificate',
        'certificate' => 'Certificate (post-secondary)',
        'diploma' => 'Diploma',
        'rpl' => 'Recognition of Prior Learning (RPL)',
    ],

    'document_types' => [
        'id_copy' => 'National ID or passport copy',
        'kcse_slip' => 'KCSE result slip / certificate',
        'passport_photo' => 'Passport-size photo',
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
