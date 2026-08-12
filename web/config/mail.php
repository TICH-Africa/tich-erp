<?php

$smtpStream = [
    'ssl' => [
        'verify_peer' => env('MAIL_VERIFY_PEER', true),
        'verify_peer_name' => env('MAIL_VERIFY_PEER', true),
    ],
];

$moduleSmtpMailer = static function (?string $username, ?string $password) use ($smtpStream): array {
    if (env('MAIL_MAILER', 'smtp') !== 'smtp') {
        return ['transport' => env('MAIL_MAILER', 'array')];
    }

    return [
        'transport' => 'smtp',
        'scheme' => env('MAIL_SCHEME'),
        'url' => env('MAIL_URL'),
        'host' => env('MAIL_HOST', '127.0.0.1'),
        'port' => env('MAIL_PORT', 2525),
        'encryption' => env('MAIL_ENCRYPTION'),
        'username' => $username,
        'password' => $password,
        'timeout' => null,
        'local_domain' => env('MAIL_EHLO_DOMAIN', parse_url((string) env('APP_URL', 'http://localhost'), PHP_URL_HOST)),
        'stream' => $smtpStream,
    ];
};

return [

    /*
    |--------------------------------------------------------------------------
    | Default Mailer
    |--------------------------------------------------------------------------
    |
    | This option controls the default mailer that is used to send all email
    | messages unless another mailer is explicitly specified when sending
    | the message. All additional mailers can be configured within the
    | "mailers" array. Examples of each type of mailer are provided.
    |
    */

    'default' => env('MAIL_MAILER', 'smtp'),

    /*
    |--------------------------------------------------------------------------
    | Mailer Configurations
    |--------------------------------------------------------------------------
    |
    | Here you may configure all of the mailers used by your application plus
    | their respective settings. Several examples have been configured for
    | you and you are free to add your own as your application requires.
    |
    | Laravel supports a variety of mail "transport" drivers that can be used
    | when delivering an email. You may specify which one you're using for
    | your mailers below. You may also add additional mailers if needed.
    |
    | Supported: "smtp", "sendmail", "mailgun", "ses", "ses-v2",
    |            "postmark", "resend", "log", "array",
    |            "failover", "roundrobin"
    |
    */

    'mailers' => [

        'smtp' => $moduleSmtpMailer(
            env('MAIL_USERNAME', env('MAIL_NOTIFICATION_ADDRESS')),
            env('MAIL_PASSWORD', env('MAIL_NOTIFICATION_PASSWORD')),
        ),

        'hr' => $moduleSmtpMailer(
            env('MAIL_HR_ADDRESS'),
            env('MAIL_HR_PASSWORD'),
        ),

        'academics' => $moduleSmtpMailer(
            env('MAIL_ACADEMICS_ADDRESS'),
            env('MAIL_ACADEMICS_PASSWORD'),
        ),

        'finance' => $moduleSmtpMailer(
            env('MAIL_FINANCE_ADDRESS'),
            env('MAIL_FINANCE_PASSWORD'),
        ),

        'otp' => $moduleSmtpMailer(
            env('MAIL_OTP_ADDRESS'),
            env('MAIL_OTP_PASSWORD'),
        ),

        'notification' => $moduleSmtpMailer(
            env('MAIL_NOTIFICATION_ADDRESS'),
            env('MAIL_NOTIFICATION_PASSWORD'),
        ),

        'ict' => $moduleSmtpMailer(
            env('MAIL_ICT_ADDRESS'),
            env('MAIL_ICT_PASSWORD'),
        ),

        'ses' => [
            'transport' => 'ses',
        ],

        'postmark' => [
            'transport' => 'postmark',
            // 'message_stream_id' => env('POSTMARK_MESSAGE_STREAM_ID'),
            // 'client' => [
            //     'timeout' => 5,
            // ],
        ],

        'resend' => [
            'transport' => 'resend',
        ],

        'sendmail' => [
            'transport' => 'sendmail',
            'path' => env('MAIL_SENDMAIL_PATH', '/usr/sbin/sendmail -bs -i'),
        ],

        'log' => [
            'transport' => 'log',
            'channel' => env('MAIL_LOG_CHANNEL'),
        ],

        'array' => [
            'transport' => 'array',
        ],

        'failover' => [
            'transport' => 'failover',
            'mailers' => [
                'smtp',
                'log',
            ],
            'retry_after' => 60,
        ],

        'roundrobin' => [
            'transport' => 'roundrobin',
            'mailers' => [
                'ses',
                'postmark',
            ],
            'retry_after' => 60,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Global "From" Address
    |--------------------------------------------------------------------------
    |
    | You may wish for all emails sent by your application to be sent from
    | the same address. Here you may specify a name and address that is
    | used globally for all emails that are sent by your application.
    |
    */

    'from' => [
        'address' => env('MAIL_FROM_ADDRESS', env('MAIL_NOTIFICATION_ADDRESS', 'notification@tich.africa')),
        'name' => env('MAIL_FROM_NAME', env('MAIL_NOTIFICATION_NAME', 'TICH ERP')),
    ],

];
