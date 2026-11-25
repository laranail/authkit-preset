<?php

declare(strict_types=1);

use Illuminate\Support\ServiceProvider;
use Simtabi\Laranail\AuthKit\Preset\Features;
use Simtabi\Laranail\AuthKit\Preset\Providers\PresetServiceProvider;

/**
 * The preset validates captchas itself, through `ValidateCaptcha` on the four unauthenticated
 * routes (register, login, forgot-password, reset-password). While it is doing that the core's own
 * Turnstile rule must stay off, or a single-use token is validated twice and the second check
 * fails a legitimate submission.
 *
 * The handover only applies when the preset's bot protection is actually on. `ValidateCaptcha`
 * returns early unless `Features::botProtection()` is in the feature list, and that feature is not
 * in the shipped default config. Forcing the core's flag off unconditionally therefore left an
 * application that had deliberately enabled Turnstile with no bot protection at all on those four
 * routes: the core's rule disabled, the preset's never running.
 *
 * Twenty lines above the offending one, two-factor already derives its value the right way:
 * `config()->set('laranail.authkit.two_factor.enabled', Features::enabled(...))`.
 *
 * ## Why this does not use `defineEnvironment`
 *
 * Measured 2026-11-25: Testbench runs `defineEnvironment()` **after** the package providers'
 * `register()`, so anything set there overwrites the provider instead of being overwritten by it.
 * A real application is the other way round — config files load first, then providers register. A
 * `defineEnvironment` version of this test passes against the broken code, for the wrong reason.
 * The application's intent therefore has to arrive from a provider registered before the preset.
 */
final class ApplicationTurnstilePreference extends ServiceProvider
{
    /** @var array<string, mixed> */
    public static array $config = [];

    public function register(): void
    {
        foreach (self::$config as $key => $value) {
            config()->set($key, $value);
        }
    }
}

trait ApplicationConfigBeforeThePreset
{
    protected function getPackageProviders($app): array
    {
        $providers = parent::getPackageProviders($app);
        $at = array_search(PresetServiceProvider::class, $providers, true);

        array_splice($providers, $at === false ? count($providers) : $at, 0, [ApplicationTurnstilePreference::class]);

        return $providers;
    }
}

uses(ApplicationConfigBeforeThePreset::class);

afterEach(function (): void {
    ApplicationTurnstilePreference::$config = [];
});

it('leaves the core turnstile alone when the preset is not protecting the forms', function (): void {
    ApplicationTurnstilePreference::$config = ['laranail.authkit.turnstile.enabled' => true];
    $this->refreshApplication();

    expect(Features::enabled(Features::botProtection()))->toBeFalse()
        ->and(config('laranail.authkit.turnstile.enabled'))->toBeTrue();
});
