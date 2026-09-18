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

    /*
    |--------------------------------------------------------------------------
    | Public link base for email bodies
    |--------------------------------------------------------------------------
    |
    | Invite / reset links inside emails must use a publicly reachable HTTPS
    | host. Private APP_URL values (127.0.0.1, 192.168.x.x) cause Gmail and
    | other providers to drop or spam-folder the message.
    |
    */
    'link_url' => env('MAIL_LINK_URL'),

    /*
    |--------------------------------------------------------------------------
    | Registration invite delivery mailbox
    |--------------------------------------------------------------------------
    |
    | hr@ / ict@ are accepted by SMTP but often never reach external inboxes on
    | this host. Invites therefore send via notification@ (proven delivery),
    | while HR/ICT branding stays in the display name and email body.
    |
    */
    'invite_delivery_module' => env('MAIL_INVITE_DELIVERY_MODULE', 'notification'),

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
        'ict' => [
            'mailer' => 'ict',
            'from' => [
                'address' => env('MAIL_ICT_ADDRESS', 'ict@tich.africa'),
                'name' => env('MAIL_ICT_NAME', 'TICH Information & Communication Technology'),
            ],
        ],
    ],

];
