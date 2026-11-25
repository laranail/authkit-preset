<?php

declare(strict_types=1);

use Simtabi\Laranail\AuthKit\Preset\Features;

/**
 * The other direction of `BotProtectionHandoverTest`: once the preset's own bot protection is on,
 * the core's Turnstile rule must be handed over, not run as well — a single-use token cannot
 * survive two validations.
 *
 * The feature list has to be in place twice over: at provider-registration time, which is when the
 * preset decides, and after `defineEnvironment`, which Testbench runs last and which would
 * otherwise restore the default list before the assertion reads it.
 */
trait PresetProtectingTheForms
{
    protected function getPackageProviders($app): array
    {
        config()->set('laranail.authkit.turnstile.enabled', true);
        config()->set('laranail.authkit-preset.features', [Features::login(), Features::botProtection()]);

        return parent::getPackageProviders($app);
    }

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('laranail.authkit-preset.features', [Features::login(), Features::botProtection()]);
    }
}

uses(PresetProtectingTheForms::class);

it('switches the core turnstile off once the preset validates captchas itself', function (): void {
    expect(Features::enabled(Features::botProtection()))->toBeTrue()
        ->and(config('laranail.authkit.turnstile.enabled'))->toBeFalse();
});
