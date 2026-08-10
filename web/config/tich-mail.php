<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Module mailboxes
    |--------------------------------------------------------------------------
    |
    | Each operational module sends automated email from its own @tich.africa
    | mailbox. Credentials are configured in .env (MAIL_{MODULE}_*).
    |
    */

    'default_module' => 'notification',

    'modules' => [
        'hr' => [
            'mailer' => 'hr',
            'from' => [
                'address' => env('MAIL_HR_ADDRESS', 'hr@tich.africa'),
                'name' => env('MAIL_HR_NAME', 'TICH Human Resources'),
            ],
        ],
        'academics' => [
            'mailer' => 'academics',
            'from' => [
                'address' => env('MAIL_ACADEMICS_ADDRESS', 'academics@tich.africa'),
                'name' => env('MAIL_ACADEMICS_NAME', 'TICH Academics'),
            ],
        ],
        'finance' => [
            'mailer' => 'finance',
            'from' => [
                'address' => env('MAIL_FINANCE_ADDRESS', 'finance@tich.africa'),
                'name' => env('MAIL_FINANCE_NAME', 'TICH Finance'),
            ],
        ],
        'otp' => [
            'mailer' => 'otp',
            'from' => [
                'address' => env('MAIL_OTP_ADDRESS', 'otp@tich.africa'),
                'name' => env('MAIL_OTP_NAME', 'TICH Security'),
            ],
        ],
        'notification' => [
            'mailer' => 'notification',
            'from' => [
                'address' => env('MAIL_NOTIFICATION_ADDRESS', 'notification@tich.africa'),
                'name' => env('MAIL_NOTIFICATION_NAME', 'TICH Notifications'),
            ],
        ],
    ],

];
