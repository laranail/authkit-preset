<?php

declare(strict_types=1);
use Simtabi\Laranail\AuthKit\Preset\Features;

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
        Features::login(),
        Features::registration(),
        Features::logout(),
        Features::updateProfileInformation(),
        Features::updatePasswords(),
        Features::emailVerification(),
    ],

    'routes' => [
        'mode' => env(key: 'AUTHKIT_PRESET_ROUTES_MODE', default: 'package'),
    ],

    'guard' => env(key: 'AUTHKIT_PRESET_GUARD', default: 'web'),

    /*
    |--------------------------------------------------------------------------
    | Additional user populations
    |--------------------------------------------------------------------------
    |
    | The routes above are mounted once for the primary guard. An application with a second kind
    | of user -- staff on an `admin` guard alongside customers on `web` -- lists it here and gets
    | the same authentication routes again, under their own URL prefix and route-name prefix.
    |
    |     'guards' => [
    |         'admin' => ['prefix' => 'admin/auth', 'name' => 'admin.'],
    |     ],
    |
    | Both keys are optional and default to '<guard>/<web prefix>' and '<guard>.'. The guard
    | itself must exist in config/auth.php.
    |
    */

    'guards' => [],

    /*
    |--------------------------------------------------------------------------
    | Route name prefix
    |--------------------------------------------------------------------------
    |
    | Route names are a flat, global registry shared with the host application and every other
    | package: a second claimant on `login` silently replaces the first, and the damage surfaces
    | far away as the wrong controller answering. Names are therefore vendor-scoped by default.
    |
    | The framework resolves several of these names itself -- the guest redirect calls
    | route('login'), the verified middleware resolves verification.notice, and the password-reset
    | and email-verification notifications build their links from password.reset and
    | verification.verify. This package rewires all of them to match whatever prefix is set here,
    | so scoping the names does not break the flows that depend on them.
    |
    | One limit is worth knowing. The rewiring is a URL-generator hook, consulted when a name is
    | not found, so `route('login')` keeps working from anywhere -- including code that does not
    | exist yet. `Route::has('login')` does not go through it: it asks the route collection
    | directly and will answer false. Code guarding a link with Route::has() must ask for the
    | scoped name, and AuthPreset::routeName('login') builds it whatever this prefix is set to.
    |
    | Swapping the route collection would close that gap, and was rejected: `route:cache` replaces
    | it with a CompiledRouteCollection, so the fallback would work in development and vanish in
    | production.
    |
    | Set this to '' for bare names, if an application or a third-party package already depends on
    | them. Nothing else needs changing: the rewiring follows the prefix.
    |
    */

    'route_name_prefix' => env(key: 'AUTHKIT_PRESET_ROUTE_NAME_PREFIX', default: 'laranail-auth.'),

    /*
    |--------------------------------------------------------------------------
    | Route Prefixes
    |--------------------------------------------------------------------------
    */

    'prefix' => [
        'web' => env(key: 'AUTHKIT_PRESET_WEB_PREFIX', default: 'auth'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Route Middleware
    |--------------------------------------------------------------------------
    */

    'middleware' => [
        'web' => ['web'],
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
        'after_login' => env(key: 'AUTHKIT_PRESET_AFTER_LOGIN', default: '/dashboard'),
        'after_registration' => env(key: 'AUTHKIT_PRESET_AFTER_REGISTRATION', default: '/dashboard'),
        'after_logout' => env(key: 'AUTHKIT_PRESET_AFTER_LOGOUT', default: '/'),
        'after_social_login' => env(key: 'AUTHKIT_PRESET_AFTER_SOCIAL_LOGIN', default: '/dashboard'),
    ],

    'social' => [
        'providers' => [
            'google',
        ],

        /*
         * Per-provider button presentation. Every key is optional: `label` falls back to the
         * provider's own name, `icon` to laranail/authkit-preset::icons.<slug>, `class` to nothing
         * and `order` to 0. This lives here rather than in laranail/authkit-social because
         * presentation is a preset concern -- the headless package carries no button styling.
         *
         *     'ui' => [
         *         'google' => ['label' => 'Continue with Google', 'order' => 10],
         *         'okta'   => ['icon' => 'icons.okta', 'order' => 20],
         *     ],
         */
        'ui' => [],
    ],

];
