<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Frontend Stack
    |--------------------------------------------------------------------------
    |
    | The frontend stack to scaffold. Blade is currently supported.
    |
    */

    'stack' => env(key: 'AUTHKIT_PRESET_STACK', default: 'blade'),

    /*
    |--------------------------------------------------------------------------
    | Features
    |--------------------------------------------------------------------------
    |
    | Toggle authentication features on or off. When a feature is disabled,
    | its corresponding routes and UI elements will not be registered.
    |
    */

    'features' => [
        Simtabi\Laranail\AuthKit\Preset\Features::login(),
        Simtabi\Laranail\AuthKit\Preset\Features::registration(),
        Simtabi\Laranail\AuthKit\Preset\Features::logout(),
        Simtabi\Laranail\AuthKit\Preset\Features::updateProfileInformation(),
        Simtabi\Laranail\AuthKit\Preset\Features::updatePasswords(),
        Simtabi\Laranail\AuthKit\Preset\Features::emailVerification(),
    ],

    'routes' => [
        'mode' => env(key: 'AUTHKIT_PRESET_ROUTES_MODE', default: 'package'),
    ],

    'guard' => env(key: 'AUTHKIT_PRESET_GUARD', default: 'web'),

    /*
    |--------------------------------------------------------------------------
    | Route Prefixes
    |--------------------------------------------------------------------------
    */

    'prefix' => [
        'web' => env(key: 'AUTHKIT_PRESET_WEB_PREFIX', default: 'auth'),
        'api' => env(key: 'AUTHKIT_PRESET_API_PREFIX', default: 'api/auth'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Route Middleware
    |--------------------------------------------------------------------------
    */

    'middleware' => [
        'web' => ['web'],
        'api' => ['api', 'throttle:60,1'],
    ],

    'bot_protection' => [
        'provider' => env(key: 'CAPTCHA_PROVIDER', default: 'turnstile'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Redirects
    |--------------------------------------------------------------------------
    |
    | Where to redirect users after a successful login.
    |
    */

    'redirects' => [
        'after_login'        => env(key: 'AUTHKIT_PRESET_AFTER_LOGIN', default: '/dashboard'),
        'after_registration' => env(key: 'AUTHKIT_PRESET_AFTER_REGISTRATION', default: '/dashboard'),
        'after_logout'       => env(key: 'AUTHKIT_PRESET_AFTER_LOGOUT', default: '/'),
        'after_social_login' => env(key: 'AUTHKIT_PRESET_AFTER_SOCIAL_LOGIN', default: '/dashboard'),
    ],

    'social' => [
        'providers' => [
            'google',
        ],
    ],

];
