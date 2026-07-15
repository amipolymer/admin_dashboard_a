<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],
    
    /*
    | OnGrid BGV — set all four in .env before calling the API (see .env.example).
    | community_id = OnGrid community ID (Postman: {{communityid}})
    | username / password = HTTP Basic auth (API client ID + secret key from OnGrid)
    */
    'ongrid' => [
        'base_url' => env('ONGRID_BASE_URL'),
        'username' => env('ONGRID_USERNAME'),
        'password' => env('ONGRID_PASSWORD'),
        'community_id' => env('ONGRID_COMMUNITY_ID'),
        // OnGrid professionId must be numeric (see Postman examples). Job title goes in otherProfession.
        'default_profession_id' => env('ONGRID_DEFAULT_PROFESSION_ID', '69'),
        // Must match OnGrid community consent text exactly (copy from OnGrid dashboard).
        // Default matches amipolymer Postman examples; override via ONGRID_CONSENT_TEXT if your community differs.
        'consent_text' => env('ONGRID_CONSENT_TEXT', 'i give consent'),
    ],
];
