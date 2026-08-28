<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;
use Simtabi\Laranail\AuthKit\Preset\Providers\PresetServiceProvider;

/**
 * vendor:publish writes into the testbench workbench, which is not cleaned between runs. A directory
 * left by an earlier revision of this package will satisfy a stale assertion forever, so this suite
 * has twice reported green on a destination that no longer existed. Clear the destination before
 * asserting anything was written to it.
 */
beforeEach(function (): void {
    File::deleteDirectory(resource_path('views/vendor/laranail'));
    File::deleteDirectory(resource_path('views/vendor/laranail-authkit-preset'));
});

it('publishes Blade views for application customization', function (): void {
    expect(resource_path('views/vendor/laranail/authkit-preset/login.blade.php'))->not->toBeFile();

    $this->artisan('vendor:publish', [
        '--tag' => 'laranail::authkit-preset-views',
    ])->assertSuccessful();

    expect(resource_path('views/vendor/laranail/authkit-preset/login.blade.php'))->toBeFile();
});

it('preserves the Auth Preset publish tags and destinations', function (): void {
    $config = ServiceProvider::pathsToPublish(
        provider: PresetServiceProvider::class,
        group: 'laranail::authkit-preset-config',
    );
    $routes = ServiceProvider::pathsToPublish(
        provider: PresetServiceProvider::class,
        group: 'laranail::authkit-preset-routes',
    );
    $views = ServiceProvider::pathsToPublish(
        provider: PresetServiceProvider::class,
        group: 'laranail::authkit-preset-views',
    );

    expect(array_values($config))->toContain(config_path('laranail/authkit-preset.php'))
        ->and(array_values($routes))->toContain(base_path('routes/laranail-authkit-preset-web.php'))
        // The API routes ship from laranail/authkit now, so this package publishes only the web set.
        ->and(array_values($views))->toContain(resource_path('views/vendor/laranail/authkit-preset'));
});
