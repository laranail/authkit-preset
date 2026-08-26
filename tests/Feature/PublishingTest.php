<?php

declare(strict_types=1);

use Illuminate\Support\ServiceProvider;
use Simtabi\Laranail\AuthKitPreset\AuthPresetServiceProvider;

it('publishes Blade views for application customization', function (): void {
    $this->artisan('vendor:publish', [
        '--tag' => 'laranail::authkit-preset-views',
    ])->assertSuccessful();

    expect(resource_path('views/vendor/laranail-authkit-preset/login.blade.php'))->toBeFile();
});

it('preserves the Auth Preset publish tags and destinations', function (): void {
    $config = ServiceProvider::pathsToPublish(
        provider: AuthPresetServiceProvider::class,
        group: 'laranail::authkit-preset-config',
    );
    $routes = ServiceProvider::pathsToPublish(
        provider: AuthPresetServiceProvider::class,
        group: 'laranail::authkit-preset-routes',
    );
    $views = ServiceProvider::pathsToPublish(
        provider: AuthPresetServiceProvider::class,
        group: 'laranail::authkit-preset-views',
    );

    expect(array_values($config))->toContain(config_path('laranail/authkit-preset.php'))
        ->and(array_values($routes))->toContain(base_path('routes/laranail-authkit-preset-web.php'))
        ->and(array_values($routes))->toContain(base_path('routes/laranail-authkit-preset-api.php'))
        ->and(array_values($views))->toContain(resource_path('views/vendor/laranail-authkit-preset'));
});
