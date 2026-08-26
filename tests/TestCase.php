<?php

declare(strict_types=1);

namespace Tests;

use Simtabi\Laranail\AuthKit\Preset\Features;
use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Simtabi\Laranail\AuthKit\Providers\AuthKitServiceProvider;
use Simtabi\Laranail\Captcha\Providers\CaptchaServiceProvider;
use Simtabi\Laranail\AuthKit\Preset\Providers\PresetServiceProvider;

abstract class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            \Laravel\Socialite\SocialiteServiceProvider::class,
            \Laravel\Fortify\FortifyServiceProvider::class,
            \Laravel\Sanctum\SanctumServiceProvider::class,
            AuthKitServiceProvider::class,
            CaptchaServiceProvider::class,
            PresetServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=');
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver'                  => 'sqlite',
            'database'                => ':memory:',
            'prefix'                  => '',
            'foreign_key_constraints' => true,
        ]);

        $app['config']->set('auth.providers.users.model', \Workbench\App\Models\User::class);
        $app['config']->set('laranail.authkit.user_model', \Workbench\App\Models\User::class);

        $app['config']->set('laranail.authkit-preset.stack', 'blade');
        $app['config']->set('laranail.authkit-preset.features', [
            Features::login(),
            Features::registration(),
            Features::logout(),
            Features::social(),
            Features::api(),
            Features::passwordReset(),
            Features::updateProfileInformation(),
            Features::updatePasswords(),
            Features::emailVerification(),
            Features::passkeys(),
        ]);
    }

    protected function defineDatabaseMigrations(): void
    {
        $authKitPasskeyMigrations = dirname(__DIR__) . '/vendor/laranail/authkit/database/migrations/passkeys';

        $this->loadMigrationsFrom(dirname(__DIR__) . '/vendor/orchestra/testbench-core/laravel/migrations');
        $this->loadMigrationsFrom(dirname(__DIR__) . '/vendor/laravel/fortify/database/migrations');
        $this->loadMigrationsFrom($authKitPasskeyMigrations);
        $this->loadMigrationsFrom(dirname(__DIR__) . '/vendor/laravel/sanctum/database/migrations');
        $this->loadMigrationsFrom(dirname(__DIR__) . '/vendor/laranail/authkit/database/migrations/social');
    }
}
