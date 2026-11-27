<?php

declare(strict_types=1);

use Illuminate\Foundation\Bootstrap\LoadConfiguration;
use Simtabi\Laranail\AuthKit\Preset\Providers\PresetServiceProvider;

it('loads an application published override at the namespaced config key', function (): void {
    $original = $this->app->configPath();
    $originalConfig = $this->app['config'];
    $temporary = sys_get_temp_dir() . '/authkit-preset-config-' . bin2hex(random_bytes(8));
    $this->app->useConfigPath($temporary);

    try {
        // These providers use Laravel's ordinary config bootstrap rather than a late-file bridge.
        mkdir($temporary . '/laranail', 0777, true);
        file_put_contents($temporary . '/laranail/authkit-preset.php', "<?php return ['guard' => 'published-guard'];");
        (new LoadConfiguration)->bootstrap($this->app);
        (new PresetServiceProvider($this->app))->register();

        expect(config('laranail.authkit-preset.guard'))->toBe('published-guard');
    } finally {
        $this->app->instance('config', $originalConfig);
        $this->app->useConfigPath($original);
        @unlink($temporary . '/laranail/authkit-preset.php');
        @rmdir($temporary . '/laranail');
        @rmdir($temporary);
    }
});
