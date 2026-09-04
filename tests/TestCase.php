<?php

declare(strict_types=1);

namespace Tests;

use Workbench\App\Models\User;
use Laravel\Fortify\FortifyServiceProvider;
use Laravel\Sanctum\SanctumServiceProvider;
use Simtabi\Laranail\AuthKit\Preset\Features;
use Laravel\Socialite\SocialiteServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Simtabi\Laranail\AuthKit\Providers\AuthKitServiceProvider;
use Simtabi\Laranail\Captcha\Providers\CaptchaServiceProvider;
use Simtabi\Laranail\AuthKit\Preset\Providers\PresetServiceProvider;
use Simtabi\Laranail\AuthKit\Social\Providers\SocialServiceProvider;

abstract class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            SocialiteServiceProvider::class,
            FortifyServiceProvider::class,
            SanctumServiceProvider::class,
            AuthKitServiceProvider::class,
            SocialServiceProvider::class,
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

        $app['config']->set('auth.providers.users.model', User::class);
        $app['config']->set('laranail.authkit.user_model', User::class);

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
        $this->loadMigrationsFrom(dirname(__DIR__) . '/vendor/laranail/authkit-social/database/migrations/social');
    }
}
