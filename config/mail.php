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

        'smtp2' => [
            'transport' => 'smtp',
            'scheme' => env('MAIL2_SCHEME'),
            'url' => env('MAIL2_URL'),
            'host' => env('MAIL2_HOST', '127.0.0.1'),
            'port' => env('MAIL2_PORT', 2525),
            'username' => env('MAIL2_USERNAME'),
            'password' => env('MAIL2_PASSWORD'),
            'timeout' => null,
            'local_domain' => env('MAIL_EHLO_DOMAIN', parse_url((string) env('APP_URL', 'http://localhost'), PHP_URL_HOST)),
        ],

        'smtp3' => [
            'transport' => 'smtp',
            'scheme' => env('MAIL3_SCHEME'),
            'url' => env('MAIL3_URL'),
            'host' => env('MAIL3_HOST', '127.0.0.1'),
            'port' => env('MAIL3_PORT', 2525),
            'username' => env('MAIL3_USERNAME'),
            'password' => env('MAIL3_PASSWORD'),
            'timeout' => null,
            'local_domain' => env('MAIL_EHLO_DOMAIN', parse_url((string) env('APP_URL', 'http://localhost'), PHP_URL_HOST)),
        ],

        'smtp4' => [
            'transport' => 'smtp',
            'scheme' => env('MAIL4_SCHEME'),
            'url' => env('MAIL4_URL'),
            'host' => env('MAIL4_HOST', '127.0.0.1'),
            'port' => env('MAIL4_PORT', 2525),
            'username' => env('MAIL4_USERNAME'),
            'password' => env('MAIL4_PASSWORD'),
            'timeout' => null,
            'local_domain' => env('MAIL_EHLO_DOMAIN', parse_url((string) env('APP_URL', 'http://localhost'), PHP_URL_HOST)),
        ],

        'smtp5' => [
            'transport' => 'smtp',
            'scheme' => env('MAIL5_SCHEME'),
            'url' => env('MAIL5_URL'),
            'host' => env('MAIL5_HOST', '127.0.0.1'),
            'port' => env('MAIL5_PORT', 2525),
            'username' => env('MAIL5_USERNAME'),
            'password' => env('MAIL5_PASSWORD'),
            'timeout' => null,
            'local_domain' => env('MAIL_EHLO_DOMAIN', parse_url((string) env('APP_URL', 'http://localhost'), PHP_URL_HOST)),
        ],

        'smtp6' => [
            'transport' => 'smtp',
            'scheme' => env('MAIL6_SCHEME'),
            'url' => env('MAIL6_URL'),
            'host' => env('MAIL6_HOST', '127.0.0.1'),
            'port' => env('MAIL6_PORT', 2525),
            'username' => env('MAIL6_USERNAME'),
            'password' => env('MAIL6_PASSWORD'),
            'timeout' => null,
            'local_domain' => env('MAIL_EHLO_DOMAIN', parse_url((string) env('APP_URL', 'http://localhost'), PHP_URL_HOST)),
        ],

        'smtp7' => [
            'transport' => 'smtp',
            'scheme' => env('MAIL7_SCHEME'),
            'url' => env('MAIL7_URL'),
            'host' => env('MAIL7_HOST', '127.0.0.1'),
            'port' => env('MAIL7_PORT', 2525),
            'username' => env('MAIL7_USERNAME'),
            'password' => env('MAIL7_PASSWORD'),
            'timeout' => null,
            'local_domain' => env('MAIL_EHLO_DOMAIN', parse_url((string) env('APP_URL', 'http://localhost'), PHP_URL_HOST)),
        ],

        'smtp8' => [
            'transport' => 'smtp',
            'scheme' => env('MAIL8_SCHEME'),
            'url' => env('MAIL8_URL'),
            'host' => env('MAIL8_HOST', '127.0.0.1'),
            'port' => env('MAIL8_PORT', 2525),
            'username' => env('MAIL8_USERNAME'),
            'password' => env('MAIL8_PASSWORD'),
            'timeout' => null,
            'local_domain' => env('MAIL_EHLO_DOMAIN', parse_url((string) env('APP_URL', 'http://localhost'), PHP_URL_HOST)),
        ],

        'smtp9' => [
            'transport' => 'smtp',
            'scheme' => env('MAIL9_SCHEME'),
            'url' => env('MAIL9_URL'),
            'host' => env('MAIL9_HOST', '127.0.0.1'),
            'port' => env('MAIL9_PORT', 2525),
            'username' => env('MAIL9_USERNAME'),
            'password' => env('MAIL9_PASSWORD'),
            'timeout' => null,
            'local_domain' => env('MAIL_EHLO_DOMAIN', parse_url((string) env('APP_URL', 'http://localhost'), PHP_URL_HOST)),
        ],

        'smtp10' => [
            'transport' => 'smtp',
            'scheme' => env('MAIL10_SCHEME'),
            'url' => env('MAIL10_URL'),
            'host' => env('MAIL10_HOST', '127.0.0.1'),
            'port' => env('MAIL10_PORT', 2525),
            'username' => env('MAIL10_USERNAME'),
            'password' => env('MAIL10_PASSWORD'),
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
            'mailers' => explode(',', env('MAIL_ROUNDROBIN_MAILERS', 'smtp,smtp2')),
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
