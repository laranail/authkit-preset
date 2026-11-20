<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;

function installerTree(string $directory): array
{
    $files = [];

    foreach (File::allFiles($directory, true) as $file) {
        $files[$file->getRelativePathname()] = file_get_contents($file->getPathname());
    }

    ksort($files);

    return $files;
}

beforeEach(function (): void {
    $this->originalBase = base_path();
    $this->originalConfig = config_path();
    $this->originalDatabase = database_path();
    $this->originalPublishes = ServiceProvider::$publishes;
    $this->originalPublishGroups = ServiceProvider::$publishGroups;
    $this->installerRoot = sys_get_temp_dir() . '/authkit-preset-install-' . bin2hex(random_bytes(8));
    File::ensureDirectoryExists($this->installerRoot);

    // Redirect every registered publish destination, so command tests cannot write into the workbench.
    foreach (['publishes', 'publishGroups'] as $registry) {
        foreach (ServiceProvider::${$registry} as &$paths) {
            foreach ($paths as &$destination) {
                if (str_starts_with($destination, $this->originalBase . '/')) {
                    $destination = $this->installerRoot . substr($destination, strlen($this->originalBase));
                }
            }
            unset($destination);
        }
        unset($paths);
    }

    $this->app->setBasePath($this->installerRoot);
    $this->app->useConfigPath($this->installerRoot . '/config');
    $this->app->useDatabasePath($this->installerRoot . '/database');

    $fixtures = [
        '.env'                                                           => "APP_NAME=Example\n",
        '.env.example'                                                   => "APP_NAME=Example\n",
        'config/laranail/authkit-preset.php'                             => file_get_contents(dirname(__DIR__, 2) . '/config/laranail/authkit-preset.php') . "\n// Application-owned configuration\n",
        'routes/laranail-authkit-preset-web.php'                         => "<?php // Application-owned routes\n",
        'resources/views/vendor/laranail/authkit-preset/owned.blade.php' => 'Application-owned view',
        'database/migrations/owned.php'                                  => '<?php // Application-owned migration',
        'resources/css/app.css'                                          => "@import 'tailwindcss';\n",
        'resources/js/app.js'                                            => "import './bootstrap';\n",
        'package.json'                                                   => "{\"private\": true}\n",
    ];

    foreach ($fixtures as $path => $contents) {
        File::ensureDirectoryExists(dirname(base_path($path)));
        File::put(base_path($path), $contents);
    }
    $model = 'InstallerUser' . bin2hex(random_bytes(8));
    File::ensureDirectoryExists(base_path('app/Models'));
    File::put(base_path('app/Models/User.php'), "<?php\nnamespace InstallerFixture;\nclass {$model} extends \\Illuminate\\Foundation\\Auth\\User\n{\n}\n");
    require base_path('app/Models/User.php');
    config()->set('auth.providers.users.model', 'InstallerFixture\\' . $model);

    $this->beforeInstall = installerTree($this->installerRoot);
});

afterEach(function (): void {
    ServiceProvider::$publishes = $this->originalPublishes;
    ServiceProvider::$publishGroups = $this->originalPublishGroups;
    $this->app->setBasePath($this->originalBase);
    $this->app->useConfigPath($this->originalConfig);
    $this->app->useDatabasePath($this->originalDatabase);
    File::deleteDirectory($this->installerRoot);
});

function installerOptions(): array
{
    return ['--no-interaction' => true, '--publish-routes' => true, '--stack' => 'blade', '--api' => true, '--passkeys' => true, '--two-factor-authentication' => true, '--bot-protection' => true, '--publish-views' => true];
}

it('previews all requested steps without changing any files even with force', function (): void {
    $this->artisan('laranail::authkit-preset.install', [...installerOptions(), '--dry-run' => true, '--force' => true])
        ->expectsOutputToContain('Would run [config]')
        ->expectsOutputToContain('Would run [migrations]')
        ->expectsOutputToContain('Dry run complete. No files changed.')
        ->assertSuccessful();

    expect(installerTree($this->installerRoot))->toBe($this->beforeInstall);
});

it('rejects unknown skip steps before changing any files', function (): void {
    $this->artisan('laranail::authkit-preset.install', [...installerOptions(), '--skip' => ['unknown-step'], '--force' => true])
        ->expectsOutputToContain('Unknown installation step(s): unknown-step.')
        ->assertFailed();

    expect(installerTree($this->installerRoot))->toBe($this->beforeInstall);
});

it('keeps each skipped step untouched while running the remaining steps', function (string $step): void {
    $this->artisan('laranail::authkit-preset.install', [...installerOptions(), '--skip' => [$step], '--force' => true])->assertSuccessful();
    $after = installerTree($this->installerRoot);
    $prefixes = [
        'model'       => ['app/Models/'],
        'config'      => ['config/'],
        'tailwind'    => ['resources/css/'],
        'passkeys'    => ['package.json', 'resources/js/'],
        'migrations'  => ['database/migrations/'],
        'routes'      => ['routes/'],
        'views'       => ['resources/views/'],
        'environment' => ['.env'],
    ];

    foreach ($this->beforeInstall as $path => $contents) {
        foreach ($prefixes[$step] as $prefix) {
            if (str_starts_with($path, $prefix)) {
                expect($after[$path] ?? null)->toBe($contents, $path);
            }
        }
    }

    foreach (array_diff_key($after, $this->beforeInstall) as $path => $contents) {
        foreach ($prefixes[$step] as $prefix) {
            expect(str_starts_with($path, $prefix))->toBeFalse($path);
        }
    }

    // An independent step still ran; skip never silently turns the entire install into a preview.
    if ($step === 'environment') {
        expect($after['routes/laranail-authkit-preset-web.php'])->not->toBe($this->beforeInstall['routes/laranail-authkit-preset-web.php']);
    } else {
        expect($after['.env'])->not->toBe($this->beforeInstall['.env']);
    }
})->with(['model', 'config', 'tailwind', 'passkeys', 'migrations', 'routes', 'views', 'environment']);

it('can skip all steps together even when force is requested', function (): void {
    $this->artisan('laranail::authkit-preset.install', [...installerOptions(), '--skip' => ['model', 'config', 'tailwind', 'passkeys', 'migrations', 'routes', 'views', 'environment'], '--force' => true])->assertSuccessful();

    expect(installerTree($this->installerRoot))->toBe($this->beforeInstall);
});

it('combines dry-run with skip and omits the skipped action from its plan', function (): void {
    $this->artisan('laranail::authkit-preset.install', [...installerOptions(), '--dry-run' => true, '--skip' => ['config']])
        ->expectsOutputToContain('Skipped [config].')
        ->doesntExpectOutputToContain('Would run [config]')
        ->expectsOutputToContain('Would run [migrations]')
        ->assertSuccessful();

    expect(installerTree($this->installerRoot))->toBe($this->beforeInstall);
});

it('preserves existing published files unless force is requested', function (): void {
    $this->artisan('laranail::authkit-preset.install', installerOptions())->assertSuccessful();
    expect(file_get_contents(base_path('routes/laranail-authkit-preset-web.php')))->toBe($this->beforeInstall['routes/laranail-authkit-preset-web.php']);

    $this->artisan('laranail::authkit-preset.install', [...installerOptions(), '--force' => true])->assertSuccessful();
    expect(file_get_contents(base_path('routes/laranail-authkit-preset-web.php')))->not->toBe($this->beforeInstall['routes/laranail-authkit-preset-web.php']);
});
