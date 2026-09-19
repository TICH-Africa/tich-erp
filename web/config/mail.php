<?php

$verifyPeer = filter_var(env('MAIL_VERIFY_PEER', true), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
if ($verifyPeer === null) {
    $verifyPeer = true;
}

$smtpStream = [
    'ssl' => [
        'verify_peer' => $verifyPeer,
        'verify_peer_name' => $verifyPeer,
    ],
];

$moduleSmtpMailer = static function (?string $username, ?string $password) use ($smtpStream): array {
    $ehloDomain = env('MAIL_EHLO_DOMAIN');
    if (! is_string($ehloDomain) || $ehloDomain === '') {
        // Never EHLO as a private APP_URL host (e.g. 192.168.x.x) — that harms deliverability.
        $mailHost = (string) env('MAIL_HOST', 'localhost');
        $ehloDomain = str_starts_with($mailHost, 'mail.')
            ? substr($mailHost, 5)
            : (preg_match('/(^|\.)tich\.africa$/i', $mailHost) === 1 ? 'tich.africa' : 'localhost');
    }

    $port = (int) env('MAIL_PORT', 2525);
    $encryption = env('MAIL_ENCRYPTION');
    $scheme = env('MAIL_SCHEME');
    if (! is_string($scheme) || $scheme === '' || strtolower($scheme) === 'null') {
        $scheme = ($port === 465 || $encryption === 'ssl') ? 'smtps' : null;
    }

    return [
        'transport' => 'smtp',
        'scheme' => $scheme,
        'url' => env('MAIL_URL'),
        'host' => env('MAIL_HOST', '127.0.0.1'),
        'port' => $port,
        'encryption' => $encryption,
        'username' => $username,
        'password' => $password,
        'timeout' => (int) env('MAIL_TIMEOUT', 30),
        'local_domain' => $ehloDomain,
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
        'name' => env('MAIL_FROM_NAME', env('MAIL_NOTIFICATION_NAME', 'TICH in Africa')),
    ],

];
