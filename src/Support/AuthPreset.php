<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AuthKit\Preset\Support;

use LogicException;
use Illuminate\Support\Facades\Validator;
use Simtabi\Laranail\Enumerator\Rules\EnumValue;
use Simtabi\Laranail\AuthKit\Preset\Enums\FrontendStack;
use Simtabi\Laranail\AuthKit\Social\Enums\SocialProvider;

class AuthPreset
{
    public static function stack(): FrontendStack
    {
        return FrontendStack::from(value: config(key: 'laranail.authkit-preset.stack', default: 'blade'));
    }

    public static function guard(): string
    {
        return config(key: 'laranail.authkit-preset.guard', default: 'web');
    }

    public static function webPrefix(): string
    {
        return config(key: 'laranail.authkit-preset.prefix.web', default: 'auth');
    }

    public static function apiPrefix(): string
    {
        return config(key: 'laranail.authkit-preset.prefix.api', default: 'api/auth');
    }

    public static function webMiddleware(): array
    {
        return config(key: 'laranail.authkit-preset.middleware.web', default: ['web']);
    }

    public static function apiMiddleware(): array
    {
        return config(key: 'laranail.authkit-preset.middleware.api', default: ['api', 'throttle:60,1']);
    }

    /**
     * The CSRF middleware class this Laravel version actually registers in the `web` group.
     *
     * Laravel 13 renamed it to PreventRequestForgery and kept ValidateCsrfToken and
     * VerifyCsrfToken as deprecated subclasses; Laravel 12 registers ValidateCsrfToken. Excluding
     * a class the group does not hold is a silent no-op -- the route stays CSRF-protected and
     * Apple's POST callback answers 419 with nothing in the log to explain it -- so this resolves
     * whichever one is really there rather than naming one and hoping.
     *
     * Order matters: on Laravel 13 all three classes exist, and only the first is registered.
     */
    public static function csrfMiddleware(): string
    {
        foreach ([
            'Illuminate\\Foundation\\Http\\Middleware\\PreventRequestForgery',
            'Illuminate\\Foundation\\Http\\Middleware\\ValidateCsrfToken',
            'Illuminate\\Foundation\\Http\\Middleware\\VerifyCsrfToken',
        ] as $class) {
            if (class_exists($class)) {
                return $class;
            }
        }

        // Unreachable on any supported version; returning the current name keeps the type honest.
        return 'Illuminate\\Foundation\\Http\\Middleware\\PreventRequestForgery';
    }

    /**
     * The prefix every route name carries.
     *
     * See the config block for why this is not empty by default, and what this package rewires so
     * that scoping the names does not break the framework flows that resolve them.
     */
    public static function routeNamePrefix(): string
    {
        return (string) config(key: 'laranail.authkit-preset.route_name_prefix', default: 'laranail-auth.');
    }

    /** A fully-qualified route name, for anything that has to resolve one by name. */
    public static function routeName(string $name): string
    {
        return self::routeNamePrefix() . $name;
    }

    /**
     * Every user population these routes are mounted for.
     *
     * The first entry is the primary one and keeps the bare route names -- `login`, `dashboard`,
     * `password.request` -- because Laravel and Fortify resolve those by exact name: the
     * framework's own guest redirect calls route('login'), and the verified middleware resolves
     * verification.notice. Renaming them breaks the framework, which is why they are not
     * vendor-scoped.
     *
     * Additional populations are configured under `guards` and each gets its own URL prefix and
     * route-name prefix, so `admin.login` sits beside `login` without either shadowing the other.
     *
     * @return array<int, array{guard: string, prefix: string, name: string}>
     */
    public static function mounts(): array
    {
        $mounts = [[
            'guard'   => self::guard(),
            'prefix'  => self::webPrefix(),
            'name'    => self::routeNamePrefix(),
            'primary' => true,
        ]];

        /** @var array<string, array{prefix?: string, name?: string}> $additional */
        $additional = config(key: 'laranail.authkit-preset.guards', default: []);

        foreach ($additional as $guard => $options) {
            if (! is_string($guard) || $guard === self::guard()) {
                continue;
            }

            $mounts[] = [
                'guard'   => $guard,
                'prefix'  => (string) ($options['prefix'] ?? $guard . '/' . self::webPrefix()),
                'name'    => self::routeNamePrefix() . (string) ($options['name'] ?? $guard . '.'),
                'primary' => false,
            ];
        }

        return $mounts;
    }

    public static function afterLoginRedirect(): string
    {
        return config(key: 'laranail.authkit-preset.redirects.after_login', default: '/dashboard');
    }

    public static function afterRegistrationRedirect(): string
    {
        return config(key: 'laranail.authkit-preset.redirects.after_registration', default: '/dashboard');
    }

    public static function afterLogoutRedirect(): string
    {
        return config(key: 'laranail.authkit-preset.redirects.after_logout', default: '/');
    }

    public static function afterSocialLoginRedirect(): string
    {
        return config(key: 'laranail.authkit-preset.redirects.after_social_login', default: '/dashboard');
    }

    /** @return array<int, string> */
    public static function enabledSocialProviders(): array
    {
        $providers = config(key: 'laranail.authkit-preset.social.providers', default: []);

        if (! is_array($providers)) {
            return [];
        }

        return array_values(array_filter(
            array: $providers,
            callback: function (mixed $provider): bool {
                if (! is_string($provider) || ! Validator::make(
                    data: ['provider' => $provider],
                    rules: ['provider' => [new EnumValue(SocialProvider::class)]],
                )->passes()) {
                    return false;
                }

                return (bool) config(key: "laranail.authkit-social.{$provider}.client_id");
            },
        ));
    }

    public static function view(string $page): string
    {
        return match (self::stack()) {
            FrontendStack::Blade => 'laranail/authkit-preset::blade.' . $page,
            default              => throw new LogicException('The configured laranail/authkit-preset stack is not installed.'),
        };
    }
}
