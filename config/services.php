<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Stripe, Mailgun, SparkPost and others. This file provides a sane
    | default location for this type of information, allowing packages
    | to have a conventional place to find your various credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
    ],

    'ses' => [
        'key' => env('SES_KEY'),
        'secret' => env('SES_SECRET'),
        'region' => env('SES_REGION'),
    ],

    'sparkpost' => [
        'secret' => env('SPARKPOST_SECRET'),
    ],

    'stripe' => [
        'model' => App\User::class,
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_CLIENT_CALLBACK'),
        'hd' => env('GOOGLE_HD', '*')
    ],

    'currencylayer' => [
        'access_key' => env('CURRENCYLAYER_API_KEY'),
        'default_rate' => env('DEFAULT_RATE_USDINR')
    ],

    'pdf_to_text' => [
        'lib_path' => env('PDF_LIB_PATH')
    ],

    'open_ai' => [
        'active' => env('OPEN_AI_ACTIVE', false),
        'api_key' => env('OPEN_AI_API_KEY'),
        'default_params' => [
            "model" => "text-davinci-003",
            "max_tokens" => 2500,
            "top_p" => 1,
            "temperature" => 0.4
        ]
    ]

];
