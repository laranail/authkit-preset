<?php

declare(strict_types=1);

namespace Tests;

use Workbench\App\Models\User;
use Laravel\Fortify\FortifyServiceProvider;
use Laravel\Sanctum\SanctumServiceProvider;
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
            FortifyServiceProvider::class,
            SanctumServiceProvider::class,
            AuthKitServiceProvider::class,
            CaptchaServiceProvider::class,
            PresetServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=');
        $app['config']->set('database.default', 'testing');
        $driver = getenv('DB_CONNECTION') ?: 'sqlite';
        $connection = $app['config']->get("database.connections.{$driver}", []);
        $connection['driver'] = $driver;

        if ($driver === 'sqlite') {
            $connection['database'] = getenv('DB_DATABASE') ?: ':memory:';
            $connection['prefix'] = '';
            $connection['foreign_key_constraints'] = true;
        }

        $app['config']->set('database.connections.testing', $connection);

        $app['config']->set('auth.providers.users.model', User::class);
        $app['config']->set('laranail.authkit.user_model', User::class);

        $app['config']->set('laranail.authkit-preset.stack', 'blade');
        $app['config']->set('laranail.authkit-preset.features', [
            Features::login(),
            Features::registration(),
            Features::logout(),
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
        \Orchestra\Testbench\load_migration_paths($this->app, [
            dirname(__DIR__) . '/vendor/orchestra/testbench-core/laravel/migrations',
            dirname(__DIR__) . '/vendor/laravel/fortify/database/migrations',
            dirname(__DIR__) . '/vendor/laranail/authkit/database/migrations/passkeys',
            dirname(__DIR__) . '/vendor/laravel/sanctum/database/migrations',
        ]);
    }
}
