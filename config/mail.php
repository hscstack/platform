<?php

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

    'default' => env('MAIL_MAILER', 'log'),

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

        'smtp' => [
            'transport' => 'smtp',
            'scheme' => env('MAIL_SCHEME'),
            'url' => env('MAIL_URL'),
            'host' => env('MAIL_HOST', '127.0.0.1'),
            'port' => env('MAIL_PORT', 2525),
            'username' => env('MAIL_USERNAME'),
            'password' => env('MAIL_PASSWORD'),
            'timeout' => null,
            'local_domain' => env('MAIL_EHLO_DOMAIN', parse_url((string) env('APP_URL', 'http://localhost'), PHP_URL_HOST)),
        ],

        'smtp_1' => [
            'transport' => 'smtp',
            'scheme' => env('MAIL_1_SCHEME'),
            'url' => env('MAIL_1_URL'),
            'host' => env('MAIL_1_HOST', env('MAIL_HOST', 'smtp.gmail.com')),
            'port' => (int) env('MAIL_1_PORT', env('MAIL_PORT', 587)),
            'username' => env('MAIL_1_USERNAME', env('MAIL_USERNAME')),
            'password' => env('MAIL_1_PASSWORD', env('MAIL_PASSWORD')),
            'timeout' => null,
            'local_domain' => env('MAIL_EHLO_DOMAIN', parse_url((string) env('APP_URL', 'http://localhost'), PHP_URL_HOST)),
        ],

        'smtp_2' => [
            'transport' => 'smtp',
            'scheme' => env('MAIL_2_SCHEME'),
            'url' => env('MAIL_2_URL'),
            'host' => env('MAIL_2_HOST', env('MAIL_HOST', 'smtp.gmail.com')),
            'port' => (int) env('MAIL_2_PORT', env('MAIL_PORT', 587)),
            'username' => env('MAIL_2_USERNAME', env('MAIL_USERNAME')),
            'password' => env('MAIL_2_PASSWORD', env('MAIL_PASSWORD')),
            'timeout' => null,
            'local_domain' => env('MAIL_EHLO_DOMAIN', parse_url((string) env('APP_URL', 'http://localhost'), PHP_URL_HOST)),
        ],

        'smtp_3' => [
            'transport' => 'smtp',
            'scheme' => env('MAIL_3_SCHEME'),
            'url' => env('MAIL_3_URL'),
            'host' => env('MAIL_3_HOST', env('MAIL_HOST', 'smtp.gmail.com')),
            'port' => (int) env('MAIL_3_PORT', env('MAIL_PORT', 587)),
            'username' => env('MAIL_3_USERNAME', env('MAIL_USERNAME')),
            'password' => env('MAIL_3_PASSWORD', env('MAIL_PASSWORD')),
            'timeout' => null,
            'local_domain' => env('MAIL_EHLO_DOMAIN', parse_url((string) env('APP_URL', 'http://localhost'), PHP_URL_HOST)),
        ],

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
            'mailers' => array_values(array_filter(explode(',', (string) env('MAIL_ROUNDROBIN_MAILERS', 'smtp_1,smtp_2,smtp_3')))),
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
        'address' => env('MAIL_FROM_ADDRESS', 'hello@example.com'),
        'name' => env('MAIL_FROM_NAME', env('APP_NAME', 'Laravel')),
    ],

];
